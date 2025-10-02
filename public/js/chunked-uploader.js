class ChunkedImageUploader {
    constructor() {
        this.selectedFiles = [];
        this.uploads = new Map();
        this.isUploading = false;
        this.chunkSize = 1024 * 1024; // 1MB chunks

        this.init();
    }

    init() {
        this.setupEventListeners();
    }

    setupEventListeners() {
        const dropZone = document.getElementById('imageDropZone');
        const fileInput = document.getElementById('imageFileInput');
        const browseBtn = document.getElementById('browseImagesBtn');
        const startBtn = document.getElementById('startUploadBtn');
        const clearBtn = document.getElementById('clearFilesBtn');

        if (browseBtn) browseBtn.addEventListener('click', () => fileInput.click());
        if (dropZone) dropZone.addEventListener('click', () => fileInput.click());
        if (startBtn) startBtn.addEventListener('click', () => this.startUploads());
        if (clearBtn) clearBtn.addEventListener('click', () => this.clearFiles());

        // Drag & Drop
        if (dropZone) {
            dropZone.addEventListener('dragover', (e) => {
                e.preventDefault();
                dropZone.classList.add('border-success');
                dropZone.querySelector('.drop-zone-overlay').classList.remove('d-none');
                dropZone.querySelector('.drop-zone-overlay').classList.add('d-flex');
            });

            dropZone.addEventListener('dragleave', (e) => {
                if (!dropZone.contains(e.relatedTarget)) {
                    dropZone.classList.remove('border-success');
                    dropZone.querySelector('.drop-zone-overlay').classList.add('d-none');
                    dropZone.querySelector('.drop-zone-overlay').classList.remove('d-flex');
                }
            });

            dropZone.addEventListener('drop', (e) => {
                e.preventDefault();
                dropZone.classList.remove('border-success');
                dropZone.querySelector('.drop-zone-overlay').classList.add('d-none');
                dropZone.querySelector('.drop-zone-overlay').classList.remove('d-flex');
                this.handleFiles(e.dataTransfer.files);
            });
        }

        if (fileInput) {
            fileInput.addEventListener('change', (e) => this.handleFiles(e.target.files));
        }
    }

    handleFiles(files) {
        const validFiles = this.validateFiles(files);
        if (validFiles.length === 0) {
            alert('No valid image files selected. Please select JPEG, PNG, GIF or WebP files.');
            return;
        }

        this.selectedFiles = [...validFiles];
        this.displaySelectedFiles();
    }

    validateFiles(files) {
        const validFiles = [];
        const allowedTypes = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
        const maxSize = 100 * 1024 * 1024; // 100MB

        for (const file of files) {
            if (!allowedTypes.includes(file.type)) {
                console.warn(`Invalid file type: ${file.name} (${file.type})`);
                continue;
            }

            if (file.size > maxSize) {
                console.warn(`File too large: ${file.name} (${this.formatBytes(file.size)})`);
                continue;
            }

            validFiles.push(file);
        }

        return validFiles;
    }

    displaySelectedFiles() {
        const container = document.getElementById('selectedFilesContainer');
        const list = document.getElementById('selectedFilesList');

        if (!container || !list) return;

        let html = '';

        this.selectedFiles.forEach((file, index) => {
            html += `
                <div class="list-group-item d-flex justify-content-between align-items-center">
                    <div>
                        <h6 class="mb-1">${file.name}</h6>
                        <small class="text-muted">Size: ${this.formatBytes(file.size)}, Type: ${file.type}</small>
                    </div>
                    <button class="btn btn-sm btn-outline-danger" onclick="window.imageUploader.removeFile(${index})">
                        <i class="bi bi-trash"></i>
                    </button>
                </div>
            `;
        });

        list.innerHTML = html;
        container.classList.remove('d-none');
    }

    removeFile(index) {
        this.selectedFiles.splice(index, 1);
        if (this.selectedFiles.length === 0) {
            document.getElementById('selectedFilesContainer')?.classList.add('d-none');
        } else {
            this.displaySelectedFiles();
        }
    }

    clearFiles() {
        this.selectedFiles = [];
        document.getElementById('selectedFilesContainer')?.classList.add('d-none');
        document.getElementById('uploadProgressContainer')?.classList.add('d-none');
        document.getElementById('imageFileInput').value = '';
    }

    async startUploads() {
        if (this.isUploading) return;
        if (this.selectedFiles.length === 0) {
            alert('Please select files first');
            return;
        }

        this.isUploading = true;
        this.showProgressContainer();

        // Disable buttons
        document.getElementById('startUploadBtn').disabled = true;
        document.getElementById('clearFilesBtn').disabled = true;

        let completedCount = 0;
        let failedCount = 0;

        for (const file of this.selectedFiles) {
            try {
                await this.uploadFile(file);
                completedCount++;
            } catch (error) {
                console.error(`Failed to upload ${file.name}:`, error);
                failedCount++;
            }
        }

        this.isUploading = false;

        // Re-enable buttons
        document.getElementById('startUploadBtn').disabled = false;
        document.getElementById('clearFilesBtn').disabled = false;

        // Show completion message
        const message = `Upload completed! ✅ ${completedCount} successful, ❌ ${failedCount} failed`;
        console.log(message);

        // Show product attachment section if any uploads succeeded
        if (completedCount > 0) {
            document.getElementById('productAttachmentContainer')?.classList.remove('d-none');
        }
    }

    async uploadFile(file) {
        try {
            console.log(`Starting upload for ${file.name}`);

            const uploadId = await this.initializeUpload(file);
            console.log(`Initialized upload with ID: ${uploadId}`);

            const totalChunks = Math.ceil(file.size / this.chunkSize);

            this.createProgressItem(uploadId, file);

            // Upload chunks
            for (let chunkNumber = 0; chunkNumber < totalChunks; chunkNumber++) {
                await this.uploadChunk(uploadId, file, chunkNumber);
                this.updateProgress(uploadId, chunkNumber + 1, totalChunks);
            }

            this.updateProgressStatus(uploadId, 'completed');
            console.log(`Successfully uploaded ${file.name}`);

        } catch (error) {
            console.error('Upload failed:', error);
            if (uploadId) {
                this.updateProgressStatus(uploadId, 'failed');
            }
            throw error;
        }
    }

    async initializeUpload(file) {
        const totalChunks = Math.ceil(file.size / this.chunkSize);
        const checksum = await this.calculateChecksum(file);

        const data = {
            filename: file.name,
            total_size: file.size,
            total_chunks: totalChunks,
            mime_type: file.type || 'image/jpeg',
            checksum: checksum
        };

        const response = await fetch('/api/uploads/initialize', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'Accept': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content || ''
            },
            body: JSON.stringify(data)
        });

        if (!response.ok) {
            throw new Error(`HTTP error! status: ${response.status}`);
        }

        const result = await response.json();

        if (!result.success) {
            throw new Error(result.message || result.error || 'Upload initialization failed');
        }

        return result.data.upload_uuid;
    }

    async uploadChunk(uploadId, file, chunkNumber) {
        const start = chunkNumber * this.chunkSize;
        const end = Math.min(start + this.chunkSize, file.size);
        const chunk = file.slice(start, end);

        const formData = new FormData();
        formData.append('upload_uuid', uploadId);
        formData.append('chunk_index', chunkNumber);
        formData.append('chunk_size', chunk.size);
        formData.append('chunk_file', chunk);

        const response = await fetch('/api/uploads/chunk', {
            method: 'POST',
            body: formData,
            headers: {
                'Accept': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content || ''
            }
        });

        if (!response.ok) {
            throw new Error(`HTTP error! status: ${response.status}`);
        }

        const result = await response.json();

        if (!result.success) {
            throw new Error(result.message || result.error || `Chunk ${chunkNumber} upload failed`);
        }

        return result;
    }

    async calculateChecksum(data) {
        try {
            const arrayBuffer = await data.arrayBuffer();
            const hashBuffer = await crypto.subtle.digest('SHA-256', arrayBuffer);
            const hashArray = Array.from(new Uint8Array(hashBuffer));
            return hashArray.map(b => b.toString(16).padStart(2, '0')).join('');
        } catch (error) {
            console.warn('Failed to calculate SHA-256 checksum, using fallback:', error);
            // Fallback for older browsers or HTTPS issues
            return 'fallback_' + Date.now() + '_' + Math.random().toString(36).substr(2, 9);
        }
    }

    showProgressContainer() {
        const container = document.getElementById('uploadProgressContainer');
        const list = document.getElementById('uploadProgressList');

        if (container) {
            container.classList.remove('d-none');
        }

        if (list) {
            list.innerHTML = ''; // Clear previous progress items
        }
    }

    createProgressItem(uploadId, file) {
        const list = document.getElementById('uploadProgressList');
        if (!list) return;

        const item = document.createElement('div');
        item.id = `progress-${uploadId}`;
        item.className = 'mb-3 p-3 border rounded';
        item.innerHTML = `
            <div class="d-flex justify-content-between align-items-center mb-2">
                <span class="fw-bold">${file.name}</span>
                <span class="badge bg-primary" id="status-${uploadId}">Uploading</span>
            </div>
            <div class="progress">
                <div class="progress-bar" id="bar-${uploadId}" role="progressbar" style="width: 0%" aria-valuenow="0" aria-valuemin="0" aria-valuemax="100"></div>
            </div>
            <div class="mt-2 small text-muted">
                <span id="progress-text-${uploadId}">0% (0 / ${Math.ceil(file.size / this.chunkSize)} chunks)</span>
            </div>
        `;

        list.appendChild(item);
    }

    updateProgress(uploadId, completed, total) {
        const progress = Math.round((completed / total) * 100);

        const bar = document.getElementById(`bar-${uploadId}`);
        const text = document.getElementById(`progress-text-${uploadId}`);

        if (bar) {
            bar.style.width = `${progress}%`;
            bar.setAttribute('aria-valuenow', progress);
        }
        if (text) {
            text.textContent = `${progress}% (${completed} / ${total} chunks)`;
        }
    }

    updateProgressStatus(uploadId, status) {
        const statusBadge = document.getElementById(`status-${uploadId}`);
        if (!statusBadge) return;

        if (status === 'completed') {
            statusBadge.textContent = 'Completed';
            statusBadge.className = 'badge bg-success';
        } else if (status === 'failed') {
            statusBadge.textContent = 'Failed';
            statusBadge.className = 'badge bg-danger';
        }
    }

    formatBytes(bytes) {
        if (bytes === 0) return '0 Bytes';
        const k = 1024;
        const sizes = ['Bytes', 'KB', 'MB', 'GB'];
        const i = Math.floor(Math.log(bytes) / Math.log(k));
        return parseFloat((bytes / Math.pow(k, i)).toFixed(2)) + ' ' + sizes[i];
    }
}

// Make it globally available
window.ChunkedImageUploader = ChunkedImageUploader;

// Auto-initialize when page loads
document.addEventListener('DOMContentLoaded', function() {
    if (document.getElementById('imageDropZone')) {
        window.imageUploader = new ChunkedImageUploader();
    }
});
