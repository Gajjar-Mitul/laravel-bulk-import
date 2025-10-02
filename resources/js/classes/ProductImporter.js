import { ApiClient } from '../utils/ApiClient';
import { NotificationManager } from './NotificationManager';

export class ProductImporter {
    constructor() {
        // Prevent multiple instances
        if (window.productImporterInstance) {
            return window.productImporterInstance;
        }
        window.productImporterInstance = this;

        this.api = new ApiClient();
        this.notification = new NotificationManager();
        this.isImporting = false;

        this.form = document.getElementById('productImportForm');
        this.fileInput = document.getElementById('csv_file');
        this.dropZone = document.getElementById('csvDropZone');

        this.init();
    }

    init() {
        if (!this.form) return;

        // Remove existing listeners first
        const newForm = this.form.cloneNode(true);
        this.form.parentNode.replaceChild(newForm, this.form);
        this.form = newForm;
        this.fileInput = this.form.querySelector('#csv_file');
        this.dropZone = document.getElementById('csvDropZone');

        this.setupEventListeners();
        this.setupDragAndDrop();
        console.log('ProductImporter initialized');
    }

    setupEventListeners() {
        this.form.addEventListener('submit', (e) => this.handleImport(e));
        this.fileInput.addEventListener('change', () => this.handleFileSelect());

        const browseCsvBtn = document.getElementById('browseCsvBtn');
        if (browseCsvBtn) {
            browseCsvBtn.addEventListener('click', () => this.fileInput.click());
        }

        const clearBtn = document.getElementById('clearForm');
        if (clearBtn) {
            clearBtn.addEventListener('click', () => this.clearForm());
        }

        const downloadBtn = document.getElementById('downloadSample');
        if (downloadBtn) {
            downloadBtn.addEventListener('click', () => this.downloadSample());
        }
    }

    setupDragAndDrop() {
        if (!this.dropZone) return;

        ['dragenter', 'dragover', 'dragleave', 'drop'].forEach(eventName => {
            this.dropZone.addEventListener(eventName, (e) => {
                e.preventDefault();
                e.stopPropagation();
            });
        });

        this.dropZone.addEventListener('dragover', () => {
            this.dropZone.classList.add('dragover');
        });

        this.dropZone.addEventListener('dragleave', () => {
            this.dropZone.classList.remove('dragover');
        });

        this.dropZone.addEventListener('drop', (e) => {
            this.dropZone.classList.remove('dragover');
            const files = e.dataTransfer.files;
            if (files.length > 0) {
                const dataTransfer = new DataTransfer();
                dataTransfer.items.add(files[0]);
                this.fileInput.files = dataTransfer.files;
                this.handleFileSelect();
            }
        });
    }

    handleFileSelect() {
        const file = this.fileInput.files[0];
        if (!file) return;

        if (!file.name.toLowerCase().endsWith('.csv')) {
            this.notification.error('Please select a CSV file.');
            return;
        }

        // Show file info
        const fileInfo = document.getElementById('selectedFileInfo');
        const fileName = document.getElementById('fileName');
        const fileSize = document.getElementById('fileSize');

        if (fileInfo && fileName && fileSize) {
            fileName.textContent = file.name;
            fileSize.textContent = this.formatBytes(file.size);
            fileInfo.classList.remove('d-none');
        }
    }

    async handleImport(e) {
        e.preventDefault();

        if (this.isImporting) return;
        this.isImporting = true;

        const file = this.fileInput.files[0];
        if (!file) {
            this.notification.error('Please select a file.');
            this.isImporting = false;
            return;
        }

        try {
            // Show progress
            const progressSection = document.getElementById('importProgress');
            if (progressSection) {
                progressSection.classList.remove('d-none');
            }

            const formData = new FormData();
            formData.append('csv_file', file);
            formData.append('update_existing', true);
            formData.append('skip_invalid', true);

            const httpResponse = await fetch('/imports/products', {
                method: 'POST',
                body: formData,
                headers: {
                    'X-CSRF-TOKEN': csrfToken,
                    'Accept': 'application/json'
                }
            });

            const response = await httpResponse.json();

            if (response.success) {
                if (response.data.status === 'queued') {
                    // Async processing - show queued message and start polling
                    this.notification.success(response.message || 'Import queued for processing');
                    this.startPollingImportStatus(response.data.import_id);
                } else {
                    // Sync processing - show immediate results
                    this.notification.success('Import completed successfully!');
                    this.showImportResults(response.data);
                    setTimeout(() => {
                        window.location.reload();
                    }, 3000);
                }
            } else {
                throw new Error(response.message || 'Import failed');
            }

        } catch (error) {
            this.notification.error('Import failed: ' + error.message);
        } finally {
            this.isImporting = false;
        }
    }

    showImportResults(data) {
        const resultsSection = document.getElementById('importResults');
        if (!resultsSection) return;

        const html = `
        <div class="alert alert-success">
            <h5><i class="bi bi-check-circle me-2"></i>Import Completed!</h5>
            <div class="row">
                <div class="col-3 text-center">
                    <div class="fs-4 fw-bold">${data.total_rows || 0}</div>
                    <small>Total</small>
                </div>
                <div class="col-3 text-center">
                    <div class="fs-4 fw-bold text-success">${data.imported_rows || 0}</div>
                    <small>Imported</small>
                </div>
                <div class="col-3 text-center">
                    <div class="fs-4 fw-bold text-info">${data.updated_rows || 0}</div>
                    <small>Updated</small>
                </div>
                <div class="col-3 text-center">
                    <div class="fs-4 fw-bold text-warning">${data.invalid_rows || 0}</div>
                    <small>Invalid</small>
                </div>
            </div>
            <p class="mt-2 mb-0"><strong>Success Rate:</strong> ${data.success_rate}</p>
        </div>
    `;

        resultsSection.innerHTML = html;
        resultsSection.classList.remove('d-none');
    }

    startPollingImportStatus(importId) {
        const statusDiv = document.getElementById('importProgress');
        if (statusDiv) {
            statusDiv.innerHTML = `
                <div class="alert alert-info">
                    <i class="bi bi-clock-history me-2"></i>
                    <strong>Processing...</strong> Your import is being processed in the background.
                    <div class="progress mt-2">
                        <div class="progress-bar progress-bar-striped progress-bar-animated" role="progressbar" style="width: 0%"></div>
                    </div>
                    <small class="text-muted">Import ID: ${importId}</small>
                </div>
            `;
        }

        // Poll every 2 seconds
        const pollInterval = setInterval(async () => {
            try {
                const response = await fetch(`/imports/status/${importId}`, {
                    headers: { 'Accept': 'application/json' }
                });
                const data = await response.json();

                if (data.success) {
                    const importData = data.data;

                    // Update progress bar
                    const progressBar = statusDiv?.querySelector('.progress-bar');
                    if (progressBar && importData.progress_percentage !== undefined) {
                        progressBar.style.width = `${importData.progress_percentage}%`;
                        progressBar.textContent = `${importData.progress_percentage}%`;
                    }

                    // Check if completed
                    if (importData.status === 'completed') {
                        clearInterval(pollInterval);
                        this.notification.success('Import completed successfully!');
                        this.showImportResults(importData);
                        setTimeout(() => window.location.reload(), 3000);
                    } else if (importData.status === 'failed') {
                        clearInterval(pollInterval);
                        this.notification.error('Import failed. Please check the import history for details.');
                        setTimeout(() => window.location.reload(), 2000);
                    }
                }
            } catch (error) {
                console.error('Error polling import status:', error);
            }
        }, 2000);

        // Stop polling after 10 minutes
        setTimeout(() => {
            clearInterval(pollInterval);
        }, 600000);
    }

    async loadRecentImports() {
        try {
            const response = await this.api.get('/api/imports/history');
            if (response.success) {
                // Display recent imports in the table
                console.log('Recent imports:', response.data);
            }
        } catch (error) {
            console.log('Failed to load recent imports:', error);
        }
    }

    clearForm() {
        this.form.reset();
        const fileInfo = document.getElementById('selectedFileInfo');
        if (fileInfo) fileInfo.classList.add('d-none');
    }

    downloadSample() {
        const csv = `sku,name,description,price,stock_quantity,category,is_active,image_url
SAMPLE001,"Sample Product",Description,19.99,100,Electronics,true,
SAMPLE002,"Another Product","Another description",29.99,50,Books,false,https://example.com/image.jpg`;

        const blob = new Blob([csv], { type: 'text/csv' });
        const url = URL.createObjectURL(blob);
        const a = document.createElement('a');
        a.href = url;
        a.download = 'sample.csv';
        a.click();
        URL.revokeObjectURL(url);

        this.notification.success('Sample downloaded!');
    }

    formatBytes(bytes) {
        if (bytes === 0) return '0 Bytes';
        const k = 1024;
        const sizes = ['Bytes', 'KB', 'MB', 'GB'];
        const i = Math.floor(Math.log(bytes) / Math.log(k));
        return parseFloat((bytes / Math.pow(k, i)).toFixed(2)) + ' ' + sizes[i];
    }
}
