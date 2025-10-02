@extends('layouts.app')

@section('title', 'File Gallery - Laravel Bulk Import')
@section('page-id', 'uploads-gallery')

@section('header')
    <h1 class="h2 mb-0 text-white">
        <i class="bi bi-images me-2"></i>
        File Gallery
    </h1>
    <p class="text-white-50 mb-0">Browse and manage all uploaded files ({{ $uploads->total() }} total)</p>
@endsection

@section('breadcrumbs')
    <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">Dashboard</a></li>
    <li class="breadcrumb-item"><a href="{{ route('uploads.index') }}">File Uploads</a></li>
    <li class="breadcrumb-item active">Gallery</li>
@endsection

@section('content')
    <div class="row">
        <div class="col-12">
            <!-- Gallery Controls -->
            <div class="card border-0 shadow-sm mb-3">
                <div class="card-body py-3">
                    <div class="row align-items-center">
                        <div class="col-md-6">
                            <div class="input-group">
                                <span class="input-group-text bg-light border-end-0">
                                    <i class="bi bi-search"></i>
                                </span>
                                <input type="text" id="searchInput" class="form-control border-start-0"
                                       placeholder="Search by filename..." value="{{ request('search') }}">
                            </div>
                        </div>
                        <div class="col-md-6 text-end">
                            <div class="btn-group me-2" role="group">
                                <input type="checkbox" class="btn-check" id="selectAll" autocomplete="off">
                                <label class="btn btn-outline-secondary btn-sm" for="selectAll">
                                    <i class="bi bi-check-all me-1"></i>Select All
                                </label>
                            </div>
                            <div class="btn-group me-2" role="group">
                                <button class="btn btn-sm btn-outline-danger" id="bulkDeleteBtn" style="display: none;">
                                    <i class="bi bi-trash me-1"></i>Delete Selected
                                </button>
                            </div>
                            <div class="btn-group" role="group">
                                <button class="btn btn-sm btn-outline-secondary" onclick="location.reload()">
                                    <i class="bi bi-arrow-clockwise me-1"></i>Refresh
                                </button>
                                <a href="{{ route('uploads.index') }}" class="btn btn-sm btn-primary">
                                    <i class="bi bi-plus-lg me-1"></i>Upload Files
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="card border-0 shadow-sm">
                <div class="card-header bg-white d-flex justify-content-between align-items-center">
                    <h5 class="card-title mb-0">
                        <i class="bi bi-images me-2"></i>
                        File Gallery
                        <span class="badge bg-secondary ms-2" id="resultsCount">{{ $uploads->count() }}</span>
                    </h5>
                    <div class="btn-group" role="group">
                        <input type="radio" class="btn-check" name="viewMode" id="gridView" autocomplete="off" checked>
                        <label class="btn btn-outline-secondary btn-sm" for="gridView">
                            <i class="bi bi-grid-3x3-gap"></i>
                        </label>
                        <input type="radio" class="btn-check" name="viewMode" id="listView" autocomplete="off">
                        <label class="btn btn-outline-secondary btn-sm" for="listView">
                            <i class="bi bi-list"></i>
                        </label>
                    </div>
                </div>
                <div class="card-body">
                    @if($uploads->isEmpty())
                        <div class="text-center py-5">
                            <i class="bi bi-images fs-1 text-muted opacity-50 d-block mb-3"></i>
                            <h5 class="text-muted">No Files Uploaded Yet</h5>
                            <p class="text-muted mb-4">
                                Upload files using the chunked upload system to see them here.
                            </p>
                            <div class="mt-3">
                                <a href="{{ route('uploads.index') }}" class="btn btn-primary">
                                    <i class="bi bi-upload me-2"></i>Start Uploading Files
                                </a>
                            </div>
                        </div>
                    @else
                        <!-- Loading Overlay -->
                        <div id="loadingOverlay" class="position-absolute top-0 start-0 w-100 h-100 bg-white bg-opacity-75 d-flex align-items-center justify-content-center" style="display: none !important; z-index: 10;">
                            <div class="text-center">
                                <div class="spinner-border text-primary" role="status"></div>
                                <div class="mt-2">Processing...</div>
                            </div>
                        </div>

                        <!-- Grid View -->
                        <div id="gridViewContainer" class="row g-3">
                            @foreach($uploads as $upload)
                                <div class="col-xl-2 col-lg-3 col-md-4 col-sm-6 upload-item" data-filename="{{ strtolower($upload->original_filename) }}" data-uuid="{{ $upload->uuid }}">
                                    <div class="card h-100 border-0 shadow-sm hover-shadow">
                                        <div class="position-relative">
                                            <div class="form-check position-absolute top-0 start-0 m-2" style="z-index: 5;">
                                                <input class="form-check-input upload-checkbox" type="checkbox" value="{{ $upload->uuid }}">
                                            </div>

                                            @if($upload->images->isNotEmpty())
                                                @php
                                                    $displayImage = $upload->images->firstWhere('variant', '256px') ?: $upload->images->first();
                                                @endphp
                                                <div class="image-container" style="position: relative; overflow: hidden; border-radius: 0.375rem 0.375rem 0 0;">
                                                    <img src="{{ route('uploads.image', ['uuid' => $upload->uuid, 'variant' => $displayImage->variant]) }}"
                                                         class="card-img-top image-preview"
                                                         style="height: 180px; object-fit: cover; transition: transform 0.2s ease;"
                                                         alt="{{ $upload->original_filename }}"
                                                         loading="lazy"
                                                         data-uuid="{{ $upload->uuid }}"
                                                         onclick="openImageModal('{{ $upload->uuid }}', '{{ $upload->original_filename }}')">

                                                    <!-- Image overlay on hover -->
                                                    <div class="position-absolute top-0 start-0 w-100 h-100 d-flex align-items-center justify-content-center bg-dark bg-opacity-50 opacity-0 image-overlay" style="transition: opacity 0.2s ease;">
                                                        <i class="bi bi-zoom-in text-white fs-2"></i>
                                                    </div>
                                                </div>

                                                <div class="position-absolute top-0 end-0 m-2">
                                                    <span class="badge bg-success">{{ ucfirst($upload->status) }}</span>
                                                </div>
                                            @else
                                                <div class="card-img-top d-flex align-items-center justify-content-center bg-light" style="height: 180px; cursor: pointer;" onclick="openImageModal('{{ $upload->uuid }}', '{{ $upload->original_filename }}')">
                                                    <i class="bi bi-file-earmark-image fs-1 text-muted"></i>
                                                </div>
                                            @endif
                                        </div>

                                        <div class="card-body p-2">
                                            <h6 class="card-title text-truncate mb-1 small" title="{{ $upload->original_filename }}">
                                                {{ $upload->original_filename }}
                                            </h6>
                                            <div class="small text-muted">
                                                <div class="d-flex justify-content-between">
                                                    <span><i class="bi bi-hdd me-1"></i>{{ number_format($upload->total_size / 1024, 1) }}KB</span>
                                                    <span><i class="bi bi-images me-1"></i>{{ $upload->images->count() }}</span>
                                                </div>
                                                <div class="text-truncate">
                                                    <i class="bi bi-calendar me-1"></i>{{ $upload->completed_at->format('M j, Y') }}
                                                </div>
                                            </div>
                                        </div>

                                        <div class="card-footer bg-transparent p-2">
                                            <div class="btn-group w-100" role="group">
                                                @if($upload->images->isNotEmpty())
                                                    <button class="btn btn-sm btn-outline-primary"
                                                            onclick="openImageModal('{{ $upload->uuid }}', '{{ $upload->original_filename }}')"
                                                            title="View">
                                                        <i class="bi bi-eye"></i>
                                                    </button>
                                                    <a href="{{ route('uploads.image', ['uuid' => $upload->uuid, 'variant' => 'original']) }}"
                                                       class="btn btn-sm btn-outline-success"
                                                       download="{{ $upload->original_filename }}"
                                                       title="Download">
                                                        <i class="bi bi-download"></i>
                                                    </a>
                                                @endif
                                                <button class="btn btn-sm btn-outline-danger"
                                                        onclick="deleteUpload('{{ $upload->uuid }}')"
                                                        title="Delete">
                                                    <i class="bi bi-trash"></i>
                                                </button>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            @endforeach
                        </div>

                        <!-- List View -->
                        <div id="listViewContainer" style="display: none;">
                            <div class="table-responsive">
                                <table class="table table-hover">
                                    <thead>
                                        <tr>
                                            <th style="width: 40px;">
                                                <input type="checkbox" id="selectAllList" class="form-check-input">
                                            </th>
                                            <th style="width: 80px;">Preview</th>
                                            <th>Filename</th>
                                            <th>Size</th>
                                            <th>Variants</th>
                                            <th>Upload Date</th>
                                            <th style="width: 140px;">Actions</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @foreach($uploads as $upload)
                                            <tr class="upload-item-list" data-filename="{{ strtolower($upload->original_filename) }}" data-uuid="{{ $upload->uuid }}">
                                                <td>
                                                    <input class="form-check-input upload-checkbox-list" type="checkbox" value="{{ $upload->uuid }}">
                                                </td>
                                                <td>
                                                    @if($upload->images->isNotEmpty())
                                                        @php
                                                            $displayImage = $upload->images->firstWhere('variant', '256px') ?: $upload->images->first();
                                                        @endphp
                                                        <img src="{{ route('uploads.image', ['uuid' => $upload->uuid, 'variant' => $displayImage->variant]) }}"
                                                             class="img-thumbnail image-preview-list"
                                                             style="width: 50px; height: 50px; object-fit: cover; cursor: pointer;"
                                                             alt="{{ $upload->original_filename }}"
                                                             onclick="openImageModal('{{ $upload->uuid }}', '{{ $upload->original_filename }}')">
                                                    @else
                                                        <div class="d-flex align-items-center justify-content-center bg-light" style="width: 50px; height: 50px;">
                                                            <i class="bi bi-file-earmark-image text-muted"></i>
                                                        </div>
                                                    @endif
                                                </td>
                                                <td>
                                                    <div class="fw-medium">{{ $upload->original_filename }}</div>
                                                    <small class="text-muted">{{ $upload->uuid }}</small>
                                                </td>
                                                <td>{{ number_format($upload->total_size / 1024, 1) }} KB</td>
                                                <td>
                                                    <span class="badge bg-secondary">{{ $upload->images->count() }} variants</span>
                                                </td>
                                                <td>
                                                    <div>{{ $upload->completed_at->format('M j, Y') }}</div>
                                                    <small class="text-muted">{{ $upload->completed_at->format('g:i A') }}</small>
                                                </td>
                                                <td>
                                                    <div class="btn-group btn-group-sm" role="group">
                                                        @if($upload->images->isNotEmpty())
                                                            <button class="btn btn-outline-primary"
                                                                    onclick="openImageModal('{{ $upload->uuid }}', '{{ $upload->original_filename }}')"
                                                                    title="View">
                                                                <i class="bi bi-eye"></i>
                                                            </button>
                                                            <a href="{{ route('uploads.image', ['uuid' => $upload->uuid, 'variant' => 'original']) }}"
                                                               class="btn btn-outline-success"
                                                               download="{{ $upload->original_filename }}"
                                                               title="Download">
                                                                <i class="bi bi-download"></i>
                                                            </a>
                                                        @endif
                                                        <button class="btn btn-outline-danger"
                                                                onclick="deleteUpload('{{ $upload->uuid }}')"
                                                                title="Delete">
                                                            <i class="bi bi-trash"></i>
                                                        </button>
                                                    </div>
                                                </td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                        </div>

                        <!-- Pagination -->
                        @if($uploads->hasPages())
                            <div class="d-flex justify-content-center mt-5">
                                <nav aria-label="Gallery pagination" class="custom-pagination">
                                    <div class="pagination-wrapper d-flex align-items-center gap-3">
                                        <!-- Previous Button -->
                                        @if($uploads->onFirstPage())
                                            <button class="btn btn-pagination-nav" disabled>
                                                <i class="bi bi-chevron-left"></i>
                                                <span class="d-none d-sm-inline ms-1">Previous</span>
                                            </button>
                                        @else
                                            <a href="{{ $uploads->appends(request()->query())->previousPageUrl() }}" class="btn btn-pagination-nav">
                                                <i class="bi bi-chevron-left"></i>
                                                <span class="d-none d-sm-inline ms-1">Previous</span>
                                            </a>
                                        @endif

                                        <!-- Page Numbers -->
                                        <div class="pagination-numbers d-flex align-items-center">
                                            @if($uploads->currentPage() > 3)
                                                <a href="{{ $uploads->appends(request()->query())->url(1) }}" class="page-number">1</a>
                                                @if($uploads->currentPage() > 4)
                                                    <span class="page-dots">...</span>
                                                @endif
                                            @endif

                                            @foreach(range(max(1, $uploads->currentPage() - 2), min($uploads->lastPage(), $uploads->currentPage() + 2)) as $page)
                                                @if($page == $uploads->currentPage())
                                                    <span class="page-number active">{{ $page }}</span>
                                                @else
                                                    <a href="{{ $uploads->appends(request()->query())->url($page) }}" class="page-number">{{ $page }}</a>
                                                @endif
                                            @endforeach

                                            @if($uploads->currentPage() < $uploads->lastPage() - 2)
                                                @if($uploads->currentPage() < $uploads->lastPage() - 3)
                                                    <span class="page-dots">...</span>
                                                @endif
                                                <a href="{{ $uploads->appends(request()->query())->url($uploads->lastPage()) }}" class="page-number">{{ $uploads->lastPage() }}</a>
                                            @endif
                                        </div>

                                        <!-- Next Button -->
                                        @if($uploads->hasMorePages())
                                            <a href="{{ $uploads->appends(request()->query())->nextPageUrl() }}" class="btn btn-pagination-nav">
                                                <span class="d-none d-sm-inline me-1">Next</span>
                                                <i class="bi bi-chevron-right"></i>
                                            </a>
                                        @else
                                            <button class="btn btn-pagination-nav" disabled>
                                                <span class="d-none d-sm-inline me-1">Next</span>
                                                <i class="bi bi-chevron-right"></i>
                                            </button>
                                        @endif
                                    </div>

                                    <!-- Page Info -->
                                    <div class="pagination-info text-center mt-3">
                                        <small class="text-muted">
                                            Showing {{ $uploads->firstItem() }}-{{ $uploads->lastItem() }} of {{ $uploads->total() }} results
                                            (Page {{ $uploads->currentPage() }} of {{ $uploads->lastPage() }})
                                        </small>
                                    </div>
                                </nav>
                            </div>
                        @endif
                    @endif
                </div>
            </div>
        </div>
    </div>

    <!-- Image Preview Modal -->
    <div class="modal fade" id="imageModal" tabindex="-1" aria-labelledby="imageModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="imageModalLabel">Image Preview</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body text-center">
                    <div id="imageModalContent">
                        <div class="spinner-border text-primary" role="status">
                            <span class="visually-hidden">Loading...</span>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <div class="btn-group w-100" role="group">
                        <button type="button" class="btn btn-outline-secondary" id="modalPrevBtn">
                            <i class="bi bi-chevron-left me-1"></i>Previous
                        </button>
                        <button type="button" class="btn btn-outline-primary" id="modalDownloadBtn">
                            <i class="bi bi-download me-1"></i>Download
                        </button>
                        <button type="button" class="btn btn-outline-danger" id="modalDeleteBtn">
                            <i class="bi bi-trash me-1"></i>Delete
                        </button>
                        <button type="button" class="btn btn-outline-secondary" id="modalNextBtn">
                            Next<i class="bi bi-chevron-right ms-1"></i>
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection

@section('page-scripts')
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script>
let currentImageUuid = null;
let allUploads = [];
let currentIndex = 0;
let selectedUploads = new Set();

// Initialize on page load
document.addEventListener('DOMContentLoaded', function() {
    initializeGallery();
    setupEventListeners();
});

function initializeGallery() {
    // Collect all uploads data
    document.querySelectorAll('.upload-item').forEach((item, index) => {
        allUploads.push({
            uuid: item.dataset.uuid,
            filename: item.dataset.filename,
            index: index
        });
    });

    // Add hover effects
    document.querySelectorAll('.image-container').forEach(container => {
        const overlay = container.querySelector('.image-overlay');
        const img = container.querySelector('img');

        container.addEventListener('mouseenter', () => {
            if (overlay) overlay.classList.remove('opacity-0');
            if (img) img.style.transform = 'scale(1.05)';
        });

        container.addEventListener('mouseleave', () => {
            if (overlay) overlay.classList.add('opacity-0');
            if (img) img.style.transform = 'scale(1)';
        });
    });
}

function setupEventListeners() {
    // Search functionality
    const searchInput = document.getElementById('searchInput');
    if (searchInput) {
        searchInput.addEventListener('input', debounce(handleSearch, 300));
    }

    // View mode toggle
    document.getElementById('gridView').addEventListener('change', () => {
        document.getElementById('gridViewContainer').style.display = 'block';
        document.getElementById('listViewContainer').style.display = 'none';
    });

    document.getElementById('listView').addEventListener('change', () => {
        document.getElementById('gridViewContainer').style.display = 'none';
        document.getElementById('listViewContainer').style.display = 'block';
    });

    // Select all functionality
    document.getElementById('selectAll').addEventListener('change', function() {
        const checkboxes = document.querySelectorAll('.upload-checkbox');
        checkboxes.forEach(cb => cb.checked = this.checked);
        updateSelectedUploads();
    });

    document.getElementById('selectAllList')?.addEventListener('change', function() {
        const checkboxes = document.querySelectorAll('.upload-checkbox-list');
        checkboxes.forEach(cb => cb.checked = this.checked);
        updateSelectedUploads();
    });

    // Individual checkbox changes
    document.querySelectorAll('.upload-checkbox, .upload-checkbox-list').forEach(checkbox => {
        checkbox.addEventListener('change', updateSelectedUploads);
    });

    // Bulk delete
    document.getElementById('bulkDeleteBtn').addEventListener('click', handleBulkDelete);

    // Modal navigation
    document.getElementById('modalPrevBtn').addEventListener('click', () => navigateImage(-1));
    document.getElementById('modalNextBtn').addEventListener('click', () => navigateImage(1));
    document.getElementById('modalDeleteBtn').addEventListener('click', () => deleteCurrentImage());
    document.getElementById('modalDownloadBtn').addEventListener('click', () => downloadCurrentImage());

    // Keyboard navigation in modal
    document.addEventListener('keydown', function(e) {
        if (document.getElementById('imageModal').classList.contains('show')) {
            if (e.key === 'ArrowLeft') navigateImage(-1);
            if (e.key === 'ArrowRight') navigateImage(1);
            if (e.key === 'Delete') deleteCurrentImage();
            if (e.key === 'Escape') bootstrap.Modal.getInstance(document.getElementById('imageModal')).hide();
        }
    });
}

function handleSearch() {
    const query = document.getElementById('searchInput').value.toLowerCase();
    let visibleCount = 0;

    document.querySelectorAll('.upload-item, .upload-item-list').forEach(item => {
        const filename = item.dataset.filename;
        const matches = filename.includes(query);

        item.style.display = matches ? '' : 'none';
        if (matches) visibleCount++;
    });

    document.getElementById('resultsCount').textContent = visibleCount;
}

function updateSelectedUploads() {
    selectedUploads.clear();

    document.querySelectorAll('.upload-checkbox:checked, .upload-checkbox-list:checked').forEach(cb => {
        selectedUploads.add(cb.value);
    });

    const bulkDeleteBtn = document.getElementById('bulkDeleteBtn');
    if (selectedUploads.size > 0) {
        bulkDeleteBtn.style.display = 'inline-block';
        bulkDeleteBtn.textContent = `Delete Selected (${selectedUploads.size})`;
    } else {
        bulkDeleteBtn.style.display = 'none';
    }
}

function handleBulkDelete() {
    if (selectedUploads.size === 0) return;

    const confirmMessage = `Are you sure you want to delete ${selectedUploads.size} selected upload(s)? This action cannot be undone.`;
    if (!confirm(confirmMessage)) return;

    showLoadingOverlay(true);

    Promise.all([...selectedUploads].map(uuid =>
        fetch(`/api/uploads/${uuid}`, {
            method: 'DELETE',
            headers: {
                'Accept': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
            }
        }).then(response => response.json())
    ))
    .then(results => {
        const successful = results.filter(r => r.success).length;
        const failed = results.length - successful;

        if (failed === 0) {
            showToast(`Successfully deleted ${successful} upload(s)`, 'success');
        } else {
            showToast(`Deleted ${successful} upload(s), failed to delete ${failed}`, 'warning');
        }

        setTimeout(() => location.reload(), 1000);
    })
    .catch(error => {
        console.error('Bulk delete error:', error);
        showToast('An error occurred during bulk delete', 'error');
    })
    .finally(() => {
        showLoadingOverlay(false);
    });
}

function openImageModal(uuid, filename) {
    currentImageUuid = uuid;
    currentIndex = allUploads.findIndex(u => u.uuid === uuid);

    const modal = new bootstrap.Modal(document.getElementById('imageModal'));
    document.getElementById('imageModalLabel').textContent = filename;

    loadImageInModal(uuid);
    updateModalNavigation();

    modal.show();
}

function loadImageInModal(uuid) {
    const modalContent = document.getElementById('imageModalContent');
    modalContent.innerHTML = '<div class="spinner-border text-primary" role="status"><span class="visually-hidden">Loading...</span></div>';

    const img = new Image();
    img.onload = function() {
        modalContent.innerHTML = `
            <img src="${this.src}" class="img-fluid" alt="Image preview" style="max-height: 70vh;">
            <div class="mt-3">
                <div class="btn-group" role="group">
                    <a href="/uploads/image/${uuid}/256px" target="_blank" class="btn btn-outline-secondary btn-sm">256px</a>
                    <a href="/uploads/image/${uuid}/512px" target="_blank" class="btn btn-outline-secondary btn-sm">512px</a>
                    <a href="/uploads/image/${uuid}/1024px" target="_blank" class="btn btn-outline-secondary btn-sm">1024px</a>
                    <a href="/uploads/image/${uuid}/original" target="_blank" class="btn btn-outline-primary btn-sm">Original</a>
                </div>
            </div>
        `;
    };
    img.onerror = function() {
        modalContent.innerHTML = '<div class="alert alert-danger">Failed to load image</div>';
    };
    img.src = `/uploads/image/${uuid}/1024px`;
}

function updateModalNavigation() {
    document.getElementById('modalPrevBtn').disabled = currentIndex === 0;
    document.getElementById('modalNextBtn').disabled = currentIndex === allUploads.length - 1;

    const downloadBtn = document.getElementById('modalDownloadBtn');
    downloadBtn.onclick = () => window.open(`/uploads/image/${currentImageUuid}/original`, '_blank');
}

function navigateImage(direction) {
    const newIndex = currentIndex + direction;
    if (newIndex >= 0 && newIndex < allUploads.length) {
        const upload = allUploads[newIndex];
        currentImageUuid = upload.uuid;
        currentIndex = newIndex;

        // Get filename from DOM
        const item = document.querySelector(`[data-uuid="${upload.uuid}"]`);
        const filename = item ? item.querySelector('.card-title, .fw-medium').textContent : 'Image';

        document.getElementById('imageModalLabel').textContent = filename;
        loadImageInModal(upload.uuid);
        updateModalNavigation();
    }
}

function deleteCurrentImage() {
    if (!currentImageUuid) return;

    if (confirm('Are you sure you want to delete this image?')) {
        deleteUpload(currentImageUuid);
    }
}

function deleteUpload(uuid) {
    showLoadingOverlay(true);

    fetch(`/api/uploads/${uuid}`, {
        method: 'DELETE',
        headers: {
            'Accept': 'application/json',
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
        }
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            showToast('Upload deleted successfully', 'success');

            // Close modal if it's open
            const modal = bootstrap.Modal.getInstance(document.getElementById('imageModal'));
            if (modal) modal.hide();

            setTimeout(() => location.reload(), 1000);
        } else {
            showToast('Failed to delete upload: ' + (data.message || 'Unknown error'), 'error');
        }
    })
    .catch(error => {
        console.error('Error:', error);
        showToast('An error occurred while deleting the upload', 'error');
    })
    .finally(() => {
        showLoadingOverlay(false);
    });
}

function showLoadingOverlay(show) {
    const overlay = document.getElementById('loadingOverlay');
    if (show) {
        overlay.style.display = 'flex';
    } else {
        overlay.style.display = 'none';
    }
}

function showToast(message, type = 'info') {
    // Create toast container if it doesn't exist
    let container = document.getElementById('toast-container');
    if (!container) {
        container = document.createElement('div');
        container.id = 'toast-container';
        container.className = 'position-fixed top-0 end-0 p-3';
        container.style.zIndex = '1056';
        document.body.appendChild(container);
    }

    const toastId = 'toast-' + Date.now();
    const bgClass = type === 'success' ? 'bg-success' : type === 'error' ? 'bg-danger' : 'bg-primary';

    const toastHtml = `
        <div id="${toastId}" class="toast ${bgClass} text-white" role="alert">
            <div class="toast-body">
                ${message}
            </div>
        </div>
    `;

    container.insertAdjacentHTML('beforeend', toastHtml);

    const toast = new bootstrap.Toast(document.getElementById(toastId), {
        delay: 3000
    });
    toast.show();

    // Remove toast element after it's hidden
    document.getElementById(toastId).addEventListener('hidden.bs.toast', function() {
        this.remove();
    });
}

function debounce(func, wait) {
    let timeout;
    return function executedFunction(...args) {
        const later = () => {
            clearTimeout(timeout);
            func(...args);
        };
        clearTimeout(timeout);
        timeout = setTimeout(later, wait);
    };
}
</script>

<style>
.hover-shadow {
    transition: box-shadow 0.15s ease-in-out;
}

.hover-shadow:hover {
    box-shadow: 0 0.5rem 1rem rgba(0, 0, 0, 0.15) !important;
}

.image-container:hover img {
    transform: scale(1.05);
}

.upload-item img, .upload-item-list img {
    cursor: pointer;
}

#loadingOverlay {
    position: absolute !important;
}

@media (max-width: 768px) {
    .col-xl-2.col-lg-3.col-md-4.col-sm-6 {
        flex: 0 0 50%;
        max-width: 50%;
    }
}
</style>
@endsection
