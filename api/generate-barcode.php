<?php
// TÜM HATA ÇIKTILARINI ENGELLE - dosya çıktısını bozmamak için
error_reporting(0);
ini_set('display_errors', '0');
ini_set('log_errors', '0');

// Output buffering başlat
ob_start();

require_once '../vendor/autoload.php';

use Picqer\Barcode\BarcodeGeneratorPNG;

// Tüm buffer'ları temizle
function clearAllBuffers() {
    while (ob_get_level() > 0) {
        ob_end_clean();
    }
}

// JSON hata döndür
function sendError($message, $code = 400) {
    clearAllBuffers();
    http_response_code($code);
    header('Content-Type: application/json; charset=utf-8');
    die(json_encode(['error' => $message], JSON_UNESCAPED_UNICODE));
}

// Sadece POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    sendError('Method not allowed', 405);
}

// JSON al
$input = json_decode(file_get_contents('php://input'), true);
if (!isset($input['items']) || !is_array($input['items']) || empty($input['items'])) {
    sendError('Geçerli veri bulunamadı');
}

$items = $input['items'];

// Geçici klasör
$tempDir = sys_get_temp_dir() . '/barcodes_' . uniqid() . '_' . time();
if (!@mkdir($tempDir, 0777, true)) {
    sendError('Geçici klasör oluşturulamadı', 500);
}

$pdfFiles = [];

try {
    $generator = new BarcodeGeneratorPNG();
    
    foreach ($items as $index => $item) {
        $productName = isset($item['productName']) ? trim($item['productName']) : '';
        $barcodeValue = isset($item['barcodeValue']) ? trim($item['barcodeValue']) : '';
        
        if (empty($barcodeValue)) continue;
        if (empty($productName)) $productName = 'Ürün ' . ($index + 1);
        
        // Barkod PNG oluştur
        try {
            @$barcodeData = $generator->getBarcode($barcodeValue, $generator::TYPE_CODE_128, 2, 80);
        } catch (Exception $e) {
            continue;
        }
        
        $barcodeFile = $tempDir . '/barcode_' . $index . '.png';
        @file_put_contents($barcodeFile, $barcodeData);
        
        // TCPDF ile PDF oluştur (60mm x 40mm etiket boyutu)
        $pdf = new TCPDF('L', 'mm', array(60, 40), true, 'UTF-8', false);
        $pdf->SetCreator('Barkod Oluşturucu');
        $pdf->SetTitle($productName);
        $pdf->setPrintHeader(false);
        $pdf->setPrintFooter(false);
        $pdf->SetMargins(2, 2, 2);
        $pdf->SetAutoPageBreak(false, 0);
        $pdf->AddPage();
        
        // Sayfa genişliği
        $pageWidth = 60;
        
        // Ürün adı - üstte ortalı, uzun isimler için otomatik küçült
        $fontSize = 9;
        $pdf->SetFont('dejavusans', 'B', $fontSize);
        $textWidth = $pdf->GetStringWidth($productName);
        
        // Eğer metin sığmıyorsa font boyutunu küçült
        while ($textWidth > ($pageWidth - 4) && $fontSize > 5) {
            $fontSize -= 0.5;
            $pdf->SetFont('dejavusans', 'B', $fontSize);
            $textWidth = $pdf->GetStringWidth($productName);
        }
        
        $pdf->SetXY(2, 3);
        $pdf->Cell($pageWidth - 4, 5, $productName, 0, 1, 'C');
        
        // Barkod görsel - ortalı
        $barcodeWidth = 50;
        $barcodeX = ($pageWidth - $barcodeWidth) / 2;
        @$pdf->Image($barcodeFile, $barcodeX, 10, $barcodeWidth, 18, 'PNG');
        
        // Barkod numarası - altta ortalı
        $pdf->SetFont('dejavusans', '', 8);
        $pdf->SetXY(0, 29);
        $pdf->Cell($pageWidth, 5, $barcodeValue, 0, 1, 'C');
        
        // Dosya adını düzelt - sonda _ olmasın
        $safeName = preg_replace('/[^a-zA-Z0-9_-]/', '_', $productName);
        $safeName = preg_replace('/_+/', '_', $safeName); // Çoklu _ temizle
        $safeName = trim($safeName, '_'); // Baş ve sondaki _ temizle
        $safeName = substr($safeName, 0, 40);
        
        $safeBarcodeValue = preg_replace('/[^a-zA-Z0-9_-]/', '', $barcodeValue);
        
        if (empty($safeName)) $safeName = 'barkod';
        
        $pdfFileName = $safeName . '-' . $safeBarcodeValue . '.pdf';
        $pdfPath = $tempDir . '/' . $pdfFileName;
        
        // PDF'i string olarak al ve dosyaya yaz
        $pdfContent = @$pdf->Output('', 'S');
        @file_put_contents($pdfPath, $pdfContent);
        
        $pdfFiles[] = ['path' => $pdfPath, 'name' => $pdfFileName];
        
        // Barkod PNG sil
        @unlink($barcodeFile);
    }
    
    if (empty($pdfFiles)) {
        @array_map('unlink', glob($tempDir . '/*'));
        @rmdir($tempDir);
        sendError('Hiç barkod oluşturulamadı');
    }
    
    // Buffer'ları temizle
    clearAllBuffers();
    
    // TEK DOSYA = PDF
    if (count($pdfFiles) === 1) {
        $file = $pdfFiles[0];
        $content = @file_get_contents($file['path']);
        $filename = basename($file['name']);
        
        header('Content-Type: application/pdf');
        header('Content-Disposition: attachment; filename="' . $filename . '"');
        header('Content-Length: ' . strlen($content));
        header('Cache-Control: no-cache, no-store, must-revalidate');
        header('Pragma: no-cache');
        header('Expires: 0');
        
        echo $content;
        
        @unlink($file['path']);
        @rmdir($tempDir);
        exit;
    }
    
    // ÇOKLU DOSYA = ZIP
    $zipPath = $tempDir . '/barkodlar.zip';
    $zip = new ZipArchive();
    
    if (@$zip->open($zipPath, ZipArchive::CREATE | ZipArchive::OVERWRITE) !== true) {
        foreach ($pdfFiles as $f) @unlink($f['path']);
        @rmdir($tempDir);
        sendError('ZIP oluşturulamadı', 500);
    }
    
    $zip->addEmptyDir('barkodlar');
    
    foreach ($pdfFiles as $file) {
        $content = @file_get_contents($file['path']);
        // Dosya adından sondaki _ temizle
        $cleanName = preg_replace('/_+\.pdf$/', '.pdf', $file['name']);
        $cleanName = preg_replace('/_+/', '_', $cleanName);
        $cleanName = preg_replace('/^_|_$/', '', pathinfo($cleanName, PATHINFO_FILENAME)) . '.pdf';
        $zip->addFromString('barkodlar/' . $cleanName, $content);
    }
    
    $zip->close();
    
    // PDF'leri sil
    foreach ($pdfFiles as $f) @unlink($f['path']);
    
    // ZIP oku ve gönder
    $zipContent = @file_get_contents($zipPath);
    
    header('Content-Type: application/zip');
    header('Content-Disposition: attachment; filename="barkodlar.zip"');
    header('Content-Length: ' . strlen($zipContent));
    header('Cache-Control: no-cache, no-store, must-revalidate');
    header('Pragma: no-cache');
    header('Expires: 0');
    
    echo $zipContent;
    
    @unlink($zipPath);
    @rmdir($tempDir);
    
} catch (Exception $e) {
    if (isset($tempDir) && is_dir($tempDir)) {
        @array_map('unlink', glob($tempDir . '/*'));
        @rmdir($tempDir);
    }
    sendError('Hata: ' . $e->getMessage(), 500);
}
