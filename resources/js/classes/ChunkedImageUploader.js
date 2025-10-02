export class ChunkedImageUploader {
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

        if (browseBtn) browseBtn.addEventListener('click', () => fileInput.click());
        if (dropZone) dropZone.addEventListener('click', () => fileInput.click());
        if (startBtn) startBtn.addEventListener('click', () => this.startUploads());

        // Drag & Drop
        if (dropZone) {
            dropZone.addEventListener('dragover', (e) => {
                e.preventDefault();
                dropZone.classList.add('border-success');
            });

            dropZone.addEventListener('dragleave', () => {
                dropZone.classList.remove('border-success');
            });

            dropZone.addEventListener('drop', (e) => {
                e.preventDefault();
                dropZone.classList.remove('border-success');
                this.handleFiles(e.dataTransfer.files);
            });
        }

        if (fileInput) {
            fileInput.addEventListener('change', (e) => this.handleFiles(e.target.files));
        }
    }

    handleFiles(files) {
        const validFiles = this.validateFiles(files);
        if (validFiles.length === 0) return;

        this.selectedFiles = [...validFiles];
        this.displaySelectedFiles();
    }

    validateFiles(files) {
        const validFiles = [];
        const allowedTypes = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
        const maxSize = 100 * 1024 * 1024; // 100MB

        for (const file of files) {
            if (!allowedTypes.includes(file.type)) {
                console.error(`Invalid file type: ${file.name}`);
                continue;
            }

            if (file.size > maxSize) {
                console.error(`File too large: ${file.name}`);
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

        let html = '<div class="row g-3">';

        this.selectedFiles.forEach((file, index) => {
            html += `
                <div class="col-md-6">
                    <div class="card">
                        <div class="card-body">
                            <h6>${file.name}</h6>
                            <p class="text-muted small">Size: ${this.formatBytes(file.size)}</p>
                            <button class="btn btn-sm btn-outline-danger" onclick="window.imageUploader.removeFile(${index})">Remove</button>
                        </div>
                    </div>
                </div>
            `;
        });

        html += '</div>';
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

    async startUploads() {
        if (this.isUploading) return;
        if (this.selectedFiles.length === 0) return;

        this.isUploading = true;
        this.showProgressContainer();

        for (const file of this.selectedFiles) {
            await this.uploadFile(file);
        }

        this.isUploading = false;
        console.log('All uploads completed!');
    }

    async uploadFile(file) {
        try {
            const uploadId = await this.initializeUpload(file);
            const totalChunks = Math.ceil(file.size / this.chunkSize);

            this.createProgressItem(uploadId, file);

            // Upload chunks
            for (let chunkNumber = 0; chunkNumber < totalChunks; chunkNumber++) {
                await this.uploadChunk(uploadId, file, chunkNumber);
                this.updateProgress(uploadId, chunkNumber + 1, totalChunks);
            }

            this.updateProgressStatus(uploadId, 'completed');

        } catch (error) {
            console.error('Upload failed:', error);
            this.updateProgressStatus(uploadId, 'failed');
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
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
            },
            body: JSON.stringify(data)
        });

        const result = await response.json();

        if (!result.success) {
            throw new Error(result.message || result.error);
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
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
            }
        });

        const result = await response.json();

        if (!result.success) {
            throw new Error(result.message || result.error);
        }

        return result;
    }    async calculateChecksum(data) {
        const arrayBuffer = await data.arrayBuffer();
        const hashBuffer = await crypto.subtle.digest('SHA-256', arrayBuffer);
        const hashArray = Array.from(new Uint8Array(hashBuffer));
        return hashArray.map(b => b.toString(16).padStart(2, '0')).join('');
    }

    showProgressContainer() {
        const container = document.getElementById('uploadProgressContainer');
        if (container) {
            container.classList.remove('d-none');
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
                <div class="progress-bar" id="bar-${uploadId}" role="progressbar" style="width: 0%"></div>
            </div>
            <div class="mt-2 small text-muted">
                <span id="progress-text-${uploadId}">0%</span>
            </div>
        `;

        list.appendChild(item);
    }

    updateProgress(uploadId, completed, total) {
        const progress = Math.round((completed / total) * 100);

        const bar = document.getElementById(`bar-${uploadId}`);
        const text = document.getElementById(`progress-text-${uploadId}`);

        if (bar) bar.style.width = `${progress}%`;
        if (text) text.textContent = `${progress}%`;
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

// Auto-initialize
if (document.getElementById('imageDropZone')) {
    window.imageUploader = new ChunkedImageUploader();
}
