@extends('layouts.app')

@section('title', 'File Uploads - Laravel Bulk Import')
@section('page-id', 'uploads-index')

@section('header')
    <h1 class="h2 mb-0 text-white">
        <i class="bi bi-cloud-upload me-2"></i>
        File Uploads
    </h1>
    <p class="text-white-50 mb-0">Upload and manage files with chunked upload support</p>
@endsection

@section('breadcrumbs')
    <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">Dashboard</a></li>
    <li class="breadcrumb-item active">File Uploads</li>
@endsection

@section('content')
    <div class="row">
        <div class="col-12">
            <div class="card border-0 shadow-sm">
                <div class="card-header bg-white d-flex justify-content-between align-items-center">
                    <h5 class="card-title mb-0">
                        <i class="bi bi-cloud-upload me-2"></i>
                        Chunked File Upload System
                    </h5>
                    <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#uploadModal">
                        <i class="bi bi-plus-lg me-1"></i>Upload Files
                    </button>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-6">
                            <div class="card bg-light">
                                <div class="card-body text-center">
                                    <i class="bi bi-upload fs-1 text-primary mb-3"></i>
                                    <h5>Upload Large Files</h5>
                                    <p class="text-muted mb-3">
                                        Upload files of any size with our chunked upload system.
                                        Files are split into smaller chunks and uploaded progressively.
                                    </p>
                                    <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#uploadModal">
                                        Start Upload
                                    </button>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="card bg-light">
                                <div class="card-body text-center">
                                    <i class="bi bi-images fs-1 text-info mb-3"></i>
                                    <h5>File Gallery</h5>
                                    <p class="text-muted mb-3">
                                        Browse and manage all uploaded files.
                                        View upload history, file details, and download files.
                                    </p>
                                    <a href="{{ route('uploads.gallery') }}" class="btn btn-info">
                                        View Gallery
                                    </a>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="mt-4">
                        <h6>Features:</h6>
                        <div class="row">
                            <div class="col-md-4">
                                <ul class="list-unstyled">
                                    <li><i class="bi bi-check-circle text-success me-2"></i>Chunked Upload Support</li>
                                    <li><i class="bi bi-check-circle text-success me-2"></i>Large File Handling</li>
                                </ul>
                            </div>
                            <div class="col-md-4">
                                <ul class="list-unstyled">
                                    <li><i class="bi bi-check-circle text-success me-2"></i>Progress Tracking</li>
                                    <li><i class="bi bi-check-circle text-success me-2"></i>Resume Interrupted Uploads</li>
                                </ul>
                            </div>
                            <div class="col-md-4">
                                <ul class="list-unstyled">
                                    <li><i class="bi bi-check-circle text-success me-2"></i>File Validation</li>
                                    <li><i class="bi bi-check-circle text-success me-2"></i>Multiple File Support</li>
                                </ul>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Upload Modal -->
    <div class="modal fade" id="uploadModal" tabindex="-1">
        <div class="modal-dialog modal-xl">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">
                        <i class="bi bi-cloud-upload me-2"></i>
                        Upload Files
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <!-- Drop Zone -->
                    <div id="imageDropZone" class="border-2 border-dashed border-primary rounded p-4 text-center mb-4 position-relative">
                        <div class="drop-zone-content">
                            <i class="bi bi-cloud-upload display-4 text-primary mb-3"></i>
                            <h5>Drag & Drop Images Here</h5>
                            <p class="text-muted mb-3">or click to browse files</p>
                            <button type="button" class="btn btn-primary" onclick="document.getElementById('imageFileInput').click()">
                                <i class="bi bi-folder-open me-2"></i>Browse Files
                            </button>
                            <input type="file" id="imageFileInput" multiple accept="image/*" class="d-none">
                        </div>
                        <div class="drop-zone-overlay position-absolute top-0 start-0 w-100 h-100 bg-primary bg-opacity-10 rounded d-none justify-content-center align-items-center">
                            <div class="text-primary">
                                <i class="bi bi-cloud-upload display-3"></i>
                                <h4 class="mt-2">Drop files here</h4>
                            </div>
                        </div>
                    </div>

                    <!-- Selected Files -->
                    <div id="selectedFilesContainer" class="d-none mb-4">
                        <h6>Selected Files</h6>
                        <div id="selectedFilesList" class="list-group"></div>
                        <div class="mt-3">
                            <button type="button" id="startUploadBtn" class="btn btn-success me-2">
                                <i class="bi bi-upload me-1"></i>Start Upload
                            </button>
                            <button type="button" id="clearFilesBtn" class="btn btn-outline-secondary">
                                <i class="bi bi-x-circle me-1"></i>Clear All
                            </button>
                        </div>
                    </div>

                    <!-- Upload Progress -->
                    <div id="uploadProgressContainer" class="d-none">
                        <h6>Upload Progress</h6>
                        <div id="uploadProgressList"></div>
                    </div>

                    <!-- Product Attachment -->
                    <div id="productAttachmentContainer" class="d-none mt-4 p-3 bg-light rounded">
                        <h6>Attach to Product (Optional)</h6>
                        <div class="row">
                            <div class="col-md-8">
                                <input type="text"
                                       id="productSkuInput"
                                       class="form-control"
                                       placeholder="Enter product SKU to attach images">
                            </div>
                            <div class="col-md-4">
                                <button type="button"
                                        id="attachToProductBtn"
                                        class="btn btn-info w-100">
                                    <i class="bi bi-link-45deg me-1"></i>Attach to Product
                                </button>
                            </div>
                        </div>
                        <small class="text-muted">Images will be processed into variants (256px, 512px, 1024px) and attached to the product.</small>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                </div>
            </div>
        </div>
    </div>
@endsection

@section('page-scripts')
    <script src="{{ asset('js/chunked-uploader.js') }}"></script>
    <script>
        // Initialize uploader when modal is shown
        const uploadModal = document.getElementById('uploadModal');
        let uploader = null;

        uploadModal.addEventListener('shown.bs.modal', function() {
            if (!uploader) {
                uploader = new ChunkedImageUploader();
            }
        });
    </script>
@endsection
