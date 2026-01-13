<?php
require_once '../vendor/autoload.php';

use PhpOffice\PhpSpreadsheet\IOFactory;

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['error' => 'Method not allowed']);
    exit;
}

if (!isset($_FILES['file']) || $_FILES['file']['error'] !== UPLOAD_ERR_OK) {
    http_response_code(400);
    echo json_encode(['error' => 'Dosya yüklenemedi']);
    exit;
}

$allowedExtensions = ['xlsx', 'xls'];
$fileExtension = strtolower(pathinfo($_FILES['file']['name'], PATHINFO_EXTENSION));

if (!in_array($fileExtension, $allowedExtensions)) {
    http_response_code(400);
    echo json_encode(['error' => 'Sadece Excel dosyaları (.xlsx, .xls) kabul edilir']);
    exit;
}

try {
    $spreadsheet = IOFactory::load($_FILES['file']['tmp_name']);
    $worksheet = $spreadsheet->getActiveSheet();
    
    $headers = [];
    $data = [];
    
    // İlk satırı başlık olarak al
    $highestColumn = $worksheet->getHighestColumn();
    $highestColumnIndex = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::columnIndexFromString($highestColumn);
    
    for ($col = 1; $col <= $highestColumnIndex; $col++) {
        $cellValue = $worksheet->getCellByColumnAndRow($col, 1)->getValue();
        $headers[] = [
            'index' => $col,
            'name' => $cellValue ?? 'Sütun ' . $col
        ];
    }
    
    // Verileri al (2. satırdan itibaren)
    $highestRow = $worksheet->getHighestRow();
    
    for ($row = 2; $row <= $highestRow; $row++) {
        $rowData = [];
        for ($col = 1; $col <= $highestColumnIndex; $col++) {
            $rowData[$col] = $worksheet->getCellByColumnAndRow($col, $row)->getValue();
        }
        // En az bir hücrede veri varsa ekle
        if (array_filter($rowData, fn($v) => $v !== null && $v !== '')) {
            $data[] = $rowData;
        }
    }
    
    echo json_encode([
        'success' => true,
        'headers' => $headers,
        'data' => $data,
        'totalRows' => count($data)
    ]);
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Excel dosyası işlenirken hata oluştu: ' . $e->getMessage()]);
}
