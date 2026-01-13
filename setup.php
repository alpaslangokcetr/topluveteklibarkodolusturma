<?php
// Uploads klasörü oluştur
$uploadsDir = __DIR__ . '/uploads';
if (!is_dir($uploadsDir)) {
    mkdir($uploadsDir, 0777, true);
}

// Temp klasörü oluştur
$tempDir = __DIR__ . '/temp';
if (!is_dir($tempDir)) {
    mkdir($tempDir, 0777, true);
}

echo "Klasörler oluşturuldu!\n";
echo "Şimdi 'composer install' komutunu çalıştırın.\n";
