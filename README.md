# Toplu Barkod Oluşturucu

Excel dosyası yükleyerek veya manuel giriş yaparak toplu barkod oluşturma uygulaması.

## Özellikler

- ✅ Excel dosyası yükleme (.xlsx, .xls)
- ✅ Sütun eşleştirme (Ürün Adı ve Barkod Değeri)
- ✅ Manuel barkod girişi
- ✅ CODE 128 Auto barkod formatı
- ✅ Tekli indirme: PDF
- ✅ Toplu indirme: ZIP (içinde PDF'ler)
- ✅ Barkod önizleme
- ✅ Drag & Drop dosya yükleme

## Kurulum

1. Composer bağımlılıklarını yükleyin:
```bash
composer install
```

2. Setup dosyasını çalıştırın:
```bash
php setup.php
```

3. Web sunucusunu başlatın:
```bash
php -S localhost:8000
```

4. Tarayıcıda açın:
```
http://localhost:8000
```

## Gereksinimler

- PHP 7.4 veya üzeri
- Composer
- PHP Extensions: zip, gd, mbstring

## Excel Formatı

Excel dosyanız şu sütunları içermelidir:
- **Ürün Adı**: Barkodun üstünde görünecek ürün ismi
- **Barkod Değeri**: CODE 128 formatında barkod numarası

Örnek:

| Ürün Adı | Barkod |
|----------|--------|
| Ürün A | 123456789 |
| Ürün B | 987654321 |

## Çıktı Formatı

Her barkod PDF'i şu bilgileri içerir:
- Ürün Adı (üstte)
- Barkod görseli (ortada)
- Barkod numarası (altta)

## Lisans

MIT
