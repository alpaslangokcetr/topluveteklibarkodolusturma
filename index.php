<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Toplu Barkod Oluşturucu</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css" rel="stylesheet">
    <link href="assets/css/style.css" rel="stylesheet">
</head>
<body>
    <div class="container py-5">
        <div class="text-center mb-5">
            <h1 class="display-4"><i class="bi bi-upc-scan me-3"></i>Toplu Barkod Oluşturucu</h1>
            <p class="lead text-muted">Excel dosyası yükleyin veya manuel olarak barkod ekleyin</p>
        </div>

        <!-- Tab Navigation -->
        <ul class="nav nav-tabs nav-justified mb-4" id="mainTabs" role="tablist">
            <li class="nav-item" role="presentation">
                <button class="nav-link active" id="excel-tab" data-bs-toggle="tab" data-bs-target="#excel" type="button" role="tab">
                    <i class="bi bi-file-earmark-excel me-2"></i>Excel Yükle
                </button>
            </li>
            <li class="nav-item" role="presentation">
                <button class="nav-link" id="manual-tab" data-bs-toggle="tab" data-bs-target="#manual" type="button" role="tab">
                    <i class="bi bi-pencil-square me-2"></i>Manuel Giriş
                </button>
            </li>
        </ul>

        <div class="tab-content" id="mainTabsContent">
            <!-- Excel Upload Tab -->
            <div class="tab-pane fade show active" id="excel" role="tabpanel">
                <div class="card shadow-sm">
                    <div class="card-body">
                        <div class="upload-area" id="uploadArea">
                            <i class="bi bi-cloud-upload display-1 text-primary"></i>
                            <h4 class="mt-3">Excel Dosyası Yükleyin</h4>
                            <p class="text-muted">Dosyayı sürükleyip bırakın veya tıklayarak seçin</p>
                            <input type="file" id="excelFile" accept=".xlsx,.xls" class="d-none">
                            <button class="btn btn-primary" onclick="document.getElementById('excelFile').click()">
                                <i class="bi bi-folder2-open me-2"></i>Dosya Seç
                            </button>
                        </div>
                        
                        <!-- Column Mapping -->
                        <div id="columnMapping" class="d-none mt-4">
                            <h5 class="mb-3"><i class="bi bi-arrow-left-right me-2"></i>Sütun Eşleştirme</h5>
                            <div class="row g-3">
                                <div class="col-md-6">
                                    <label class="form-label">Ürün Adı Sütunu</label>
                                    <select class="form-select" id="productNameColumn">
                                        <option value="">Seçiniz...</option>
                                    </select>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label">Barkod Değeri Sütunu</label>
                                    <select class="form-select" id="barcodeValueColumn">
                                        <option value="">Seçiniz...</option>
                                    </select>
                                </div>
                            </div>
                            <button class="btn btn-success mt-3" id="processExcel">
                                <i class="bi bi-check-circle me-2"></i>Eşleştirmeyi Uygula
                            </button>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Manual Entry Tab -->
            <div class="tab-pane fade" id="manual" role="tabpanel">
                <div class="card shadow-sm">
                    <div class="card-body">
                        <div id="manualEntries">
                            <div class="manual-entry-row row g-3 mb-3" data-index="0">
                                <div class="col-md-4">
                                    <label class="form-label">Ürün Adı</label>
                                    <input type="text" class="form-control product-name" placeholder="Ürün adını girin">
                                </div>
                                <div class="col-md-4">
                                    <label class="form-label">Barkod Değeri</label>
                                    <input type="text" class="form-control barcode-value" placeholder="Barkod numarasını girin">
                                </div>
                                <div class="col-md-2 d-flex align-items-end">
                                    <button class="btn btn-success add-to-list w-100" title="Listeye Ekle">
                                        <i class="bi bi-plus-circle"></i>
                                    </button>
                                </div>
                                <div class="col-md-2 d-flex align-items-end">
                                    <button class="btn btn-danger remove-entry w-100" disabled title="Satırı Sil">
                                        <i class="bi bi-trash"></i>
                                    </button>
                                </div>
                            </div>
                        </div>
                        <button class="btn btn-outline-primary" id="addEntry">
                            <i class="bi bi-plus-circle me-2"></i>Yeni Satır Ekle
                        </button>
                    </div>
                </div>
            </div>
        </div>

        <!-- Preview Section -->
        <div class="card shadow-sm mt-4" id="previewSection" style="display: none;">
            <div class="card-header bg-primary text-white d-flex justify-content-between align-items-center">
                <h5 class="mb-0"><i class="bi bi-eye me-2"></i>Önizleme</h5>
                <span class="badge bg-light text-primary" id="itemCount">0 ürün</span>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-hover" id="previewTable">
                        <thead>
                            <tr>
                                <th width="50">#</th>
                                <th>Ürün Adı</th>
                                <th>Barkod Değeri</th>
                                <th width="200">Önizleme</th>
                                <th width="100">İşlem</th>
                            </tr>
                        </thead>
                        <tbody id="previewBody">
                        </tbody>
                    </table>
                </div>
            </div>
            <div class="card-footer">
                <div class="d-flex justify-content-between align-items-center">
                    <button class="btn btn-outline-danger" id="clearAll">
                        <i class="bi bi-trash me-2"></i>Tümünü Temizle
                    </button>
                    <button class="btn btn-success btn-lg" id="downloadBtn">
                        <i class="bi bi-download me-2"></i>İndir
                    </button>
                </div>
            </div>
        </div>

        <!-- Loading Overlay -->
        <div id="loadingOverlay" class="d-none">
            <div class="spinner-border text-primary" role="status">
                <span class="visually-hidden">Yükleniyor...</span>
            </div>
            <p class="mt-3">İşlem yapılıyor...</p>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/jsbarcode@3.11.5/dist/JsBarcode.all.min.js"></script>
    <script src="assets/js/app.js"></script>
</body>
</html>
