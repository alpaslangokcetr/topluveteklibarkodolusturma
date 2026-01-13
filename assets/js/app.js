// Global state
let excelData = [];
let barcodeItems = [];

// DOM Elements
const uploadArea = document.getElementById('uploadArea');
const excelFile = document.getElementById('excelFile');
const columnMapping = document.getElementById('columnMapping');
const productNameColumn = document.getElementById('productNameColumn');
const barcodeValueColumn = document.getElementById('barcodeValueColumn');
const processExcel = document.getElementById('processExcel');
const manualEntries = document.getElementById('manualEntries');
const addEntry = document.getElementById('addEntry');
const previewSection = document.getElementById('previewSection');
const previewBody = document.getElementById('previewBody');
const itemCount = document.getElementById('itemCount');
const downloadBtn = document.getElementById('downloadBtn');
const clearAll = document.getElementById('clearAll');
const loadingOverlay = document.getElementById('loadingOverlay');

// Utility Functions
function showLoading() {
    loadingOverlay.classList.remove('d-none');
}

function hideLoading() {
    loadingOverlay.classList.add('d-none');
}

function showToast(message, type = 'success') {
    const toast = document.createElement('div');
    toast.className = `alert alert-${type} position-fixed top-0 end-0 m-3`;
    toast.style.zIndex = '10000';
    toast.style.animation = 'fadeIn 0.3s ease';
    toast.innerHTML = `
        <i class="bi bi-${type === 'success' ? 'check-circle' : 'exclamation-circle'} me-2"></i>
        ${message}
    `;
    document.body.appendChild(toast);
    
    setTimeout(() => {
        toast.remove();
    }, 3000);
}

// Drag & Drop Events
uploadArea.addEventListener('dragover', (e) => {
    e.preventDefault();
    uploadArea.classList.add('dragover');
});

uploadArea.addEventListener('dragleave', () => {
    uploadArea.classList.remove('dragover');
});

uploadArea.addEventListener('drop', (e) => {
    e.preventDefault();
    uploadArea.classList.remove('dragover');
    
    const files = e.dataTransfer.files;
    if (files.length > 0) {
        handleFileUpload(files[0]);
    }
});

uploadArea.addEventListener('click', () => {
    excelFile.click();
});

excelFile.addEventListener('change', (e) => {
    if (e.target.files.length > 0) {
        handleFileUpload(e.target.files[0]);
    }
});

// File Upload Handler
async function handleFileUpload(file) {
    const validExtensions = ['xlsx', 'xls'];
    const extension = file.name.split('.').pop().toLowerCase();
    
    if (!validExtensions.includes(extension)) {
        showToast('Sadece Excel dosyaları (.xlsx, .xls) kabul edilir', 'danger');
        return;
    }
    
    showLoading();
    
    const formData = new FormData();
    formData.append('file', file);
    
    try {
        const response = await fetch('api/parse-excel.php', {
            method: 'POST',
            body: formData
        });
        
        const result = await response.json();
        
        if (result.error) {
            showToast(result.error, 'danger');
            hideLoading();
            return;
        }
        
        excelData = result.data;
        
        // Populate column selects
        productNameColumn.innerHTML = '<option value="">Seçiniz...</option>';
        barcodeValueColumn.innerHTML = '<option value="">Seçiniz...</option>';
        
        result.headers.forEach(header => {
            const option1 = new Option(header.name, header.index);
            const option2 = new Option(header.name, header.index);
            productNameColumn.appendChild(option1);
            barcodeValueColumn.appendChild(option2);
        });
        
        // Show file info
        uploadArea.innerHTML = `
            <div class="file-info">
                <i class="bi bi-file-earmark-excel-fill"></i>
                <div class="file-details">
                    <div class="file-name">${file.name}</div>
                    <div class="file-size">${formatFileSize(file.size)} - ${result.totalRows} satır bulundu</div>
                </div>
                <button class="btn btn-outline-primary btn-sm" onclick="resetUpload()">
                    <i class="bi bi-arrow-repeat me-1"></i>Değiştir
                </button>
            </div>
        `;
        
        columnMapping.classList.remove('d-none');
        showToast(`${result.totalRows} satır başarıyla yüklendi`);
        
    } catch (error) {
        showToast('Dosya yüklenirken bir hata oluştu', 'danger');
        console.error(error);
    }
    
    hideLoading();
}

function resetUpload() {
    uploadArea.innerHTML = `
        <i class="bi bi-cloud-upload display-1 text-primary"></i>
        <h4 class="mt-3">Excel Dosyası Yükleyin</h4>
        <p class="text-muted">Dosyayı sürükleyip bırakın veya tıklayarak seçin</p>
        <input type="file" id="excelFile" accept=".xlsx,.xls" class="d-none">
        <button class="btn btn-primary" onclick="document.getElementById('excelFile').click()">
            <i class="bi bi-folder2-open me-2"></i>Dosya Seç
        </button>
    `;
    
    // Re-attach event listener
    document.getElementById('excelFile').addEventListener('change', (e) => {
        if (e.target.files.length > 0) {
            handleFileUpload(e.target.files[0]);
        }
    });
    
    columnMapping.classList.add('d-none');
    excelData = [];
}

function formatFileSize(bytes) {
    if (bytes === 0) return '0 Bytes';
    const k = 1024;
    const sizes = ['Bytes', 'KB', 'MB', 'GB'];
    const i = Math.floor(Math.log(bytes) / Math.log(k));
    return parseFloat((bytes / Math.pow(k, i)).toFixed(2)) + ' ' + sizes[i];
}

// Process Excel Data
processExcel.addEventListener('click', () => {
    const productCol = parseInt(productNameColumn.value);
    const barcodeCol = parseInt(barcodeValueColumn.value);
    
    if (!productCol || !barcodeCol) {
        showToast('Lütfen tüm sütunları eşleştirin', 'warning');
        return;
    }
    
    barcodeItems = excelData.map(row => ({
        productName: row[productCol] || '',
        barcodeValue: String(row[barcodeCol] || '')
    })).filter(item => item.barcodeValue.trim() !== '');
    
    updatePreview();
    showToast(`${barcodeItems.length} barkod listeye eklendi`);
});

// Manual Entry Functions
addEntry.addEventListener('click', () => {
    const entries = manualEntries.querySelectorAll('.manual-entry-row');
    const newIndex = entries.length;
    
    const newRow = document.createElement('div');
    newRow.className = 'manual-entry-row row g-3 mb-3';
    newRow.dataset.index = newIndex;
    newRow.innerHTML = `
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
            <button class="btn btn-danger remove-entry w-100" title="Satırı Sil">
                <i class="bi bi-trash"></i>
            </button>
        </div>
    `;
    
    manualEntries.appendChild(newRow);
    
    // Enable all remove buttons if more than one row
    updateRemoveButtons();
    
    // Attach remove event
    newRow.querySelector('.remove-entry').addEventListener('click', () => {
        newRow.remove();
        updateRemoveButtons();
    });
    
    // Attach add to list event
    attachAddToListEvent(newRow);
    
    // Attach input events for real-time preview
    attachInputEvents(newRow);
});

function updateRemoveButtons() {
    const rows = manualEntries.querySelectorAll('.manual-entry-row');
    rows.forEach(row => {
        const btn = row.querySelector('.remove-entry');
        btn.disabled = rows.length <= 1;
    });
}

function attachInputEvents(row) {
    const inputs = row.querySelectorAll('input');
    inputs.forEach(input => {
        input.addEventListener('blur', () => {
            updateManualItems();
        });
    });
}

// Add to list function for a single row
function attachAddToListEvent(row) {
    const addBtn = row.querySelector('.add-to-list');
    if (addBtn) {
        addBtn.addEventListener('click', () => {
            const productName = row.querySelector('.product-name').value.trim();
            const barcodeValue = row.querySelector('.barcode-value').value.trim();
            
            if (!barcodeValue) {
                showToast('Lütfen barkod değeri girin', 'warning');
                return;
            }
            
            // Check if already exists
            const exists = barcodeItems.some(item => item.barcodeValue === barcodeValue);
            if (exists) {
                showToast('Bu barkod zaten listede mevcut', 'warning');
                return;
            }
            
            barcodeItems.push({
                productName: productName || 'İsimsiz Ürün',
                barcodeValue: barcodeValue,
                source: 'manual'
            });
            
            // Clear inputs
            row.querySelector('.product-name').value = '';
            row.querySelector('.barcode-value').value = '';
            
            updatePreview();
            showToast('Barkod listeye eklendi');
        });
    }
}

// Attach events to initial row
document.querySelectorAll('.manual-entry-row').forEach(row => {
    attachInputEvents(row);
    attachAddToListEvent(row);
    row.querySelector('.remove-entry').addEventListener('click', () => {
        const rows = manualEntries.querySelectorAll('.manual-entry-row');
        if (rows.length > 1) {
            row.remove();
            updateRemoveButtons();
        }
    });
});

function updateManualItems() {
    const rows = manualEntries.querySelectorAll('.manual-entry-row');
    const manualItems = [];
    
    rows.forEach(row => {
        const productName = row.querySelector('.product-name').value.trim();
        const barcodeValue = row.querySelector('.barcode-value').value.trim();
        
        if (barcodeValue) {
            manualItems.push({ productName, barcodeValue });
        }
    });
    
    // Merge with existing items (from Excel)
    const excelItems = barcodeItems.filter(item => item.source === 'excel');
    barcodeItems = [...excelItems, ...manualItems.map(item => ({ ...item, source: 'manual' }))];
    
    updatePreview();
}

// Tab change event - update manual items when switching tabs
document.getElementById('manual-tab').addEventListener('shown.bs.tab', () => {
    updateManualItems();
});

// Preview Functions
function updatePreview() {
    if (barcodeItems.length === 0) {
        previewSection.style.display = 'none';
        return;
    }
    
    previewSection.style.display = 'block';
    previewBody.innerHTML = '';
    itemCount.textContent = `${barcodeItems.length} ürün`;
    
    barcodeItems.forEach((item, index) => {
        const row = document.createElement('tr');
        row.innerHTML = `
            <td>${index + 1}</td>
            <td><strong>${item.productName || 'İsimsiz Ürün'}</strong></td>
            <td><code>${item.barcodeValue}</code></td>
            <td>
                <div class="barcode-preview">
                    <svg id="barcode-${index}"></svg>
                </div>
            </td>
            <td>
                <button class="btn btn-outline-danger btn-sm remove-item" data-index="${index}">
                    <i class="bi bi-trash"></i>
                </button>
            </td>
        `;
        previewBody.appendChild(row);
        
        // Generate barcode preview
        try {
            JsBarcode(`#barcode-${index}`, item.barcodeValue, {
                format: 'CODE128',
                width: 1.5,
                height: 40,
                displayValue: false,
                margin: 5
            });
        } catch (e) {
            row.querySelector('.barcode-preview').innerHTML = '<span class="text-danger small">Geçersiz barkod</span>';
        }
    });
    
    // Attach remove events
    document.querySelectorAll('.remove-item').forEach(btn => {
        btn.addEventListener('click', (e) => {
            const index = parseInt(e.currentTarget.dataset.index);
            barcodeItems.splice(index, 1);
            updatePreview();
        });
    });
}

// Download Handler
downloadBtn.addEventListener('click', async () => {
    if (barcodeItems.length === 0) {
        showToast('İndirilecek barkod bulunamadı', 'warning');
        return;
    }
    
    showLoading();
    
    try {
        const response = await fetch('api/generate-barcode.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json'
            },
            body: JSON.stringify({ items: barcodeItems })
        });
        
        if (!response.ok) {
            const error = await response.json();
            throw new Error(error.error || 'İndirme hatası');
        }
        
        const blob = await response.blob();
        const contentType = response.headers.get('Content-Type');
        const contentDisposition = response.headers.get('Content-Disposition');
        
        let filename = 'barkodlar.zip';
        if (contentDisposition) {
            const match = contentDisposition.match(/filename="([^"]+)"/);
            if (match) {
                filename = match[1];
            }
        }
        
        // Dosya adını temizle - sondaki _ ve uzantıdan önceki _ karakterlerini kaldır
        filename = filename
            .replace(/[_]+$/, '')           // Sondaki _ temizle (örn: file.pdf_ -> file.pdf)
            .replace(/[_]+(\.[^.]+)$/, '$1') // Uzantıdan önceki _ temizle (örn: file_.pdf -> file.pdf)
            .replace(/[_]+\./, '.');         // Noktadan önceki _ temizle
        
        // Download file
        const url = window.URL.createObjectURL(blob);
        const a = document.createElement('a');
        a.href = url;
        a.download = filename;
        document.body.appendChild(a);
        a.click();
        window.URL.revokeObjectURL(url);
        a.remove();
        
        showToast(`${barcodeItems.length} barkod başarıyla indirildi`);
        
    } catch (error) {
        showToast(error.message, 'danger');
        console.error(error);
    }
    
    hideLoading();
});

// Clear All
clearAll.addEventListener('click', () => {
    if (confirm('Tüm barkodları silmek istediğinizden emin misiniz?')) {
        barcodeItems = [];
        excelData = [];
        resetUpload();
        
        // Reset manual entries
        manualEntries.innerHTML = `
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
        `;
        
        // Re-attach events
        document.querySelectorAll('.manual-entry-row').forEach(row => {
            attachInputEvents(row);
            attachAddToListEvent(row);
        });
        
        updatePreview();
        showToast('Tüm veriler temizlendi');
    }
});

// Initialize
updatePreview();
