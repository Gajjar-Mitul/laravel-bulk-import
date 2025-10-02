<div class="card shadow-sm">
    <div class="card-header bg-white">
        <h5 class="card-title mb-0">
            <i class="bi bi-upload me-2"></i>
            Import Products from CSV
        </h5>
    </div>
    <div class="card-body">
        <form id="productImportForm" action="{{ route('imports.products.store') }}" method="POST" enctype="multipart/form-data">
            @csrf

            <!-- File Upload Zone -->
            <div class="mb-4">
                <label for="csv_file" class="form-label fw-semibold">Select CSV File</label>
                <div class="file-upload-zone" id="csvDropZone">
                    <i class="bi bi-cloud-arrow-up fs-1 text-muted mb-3"></i>
                    <h6 class="fw-semibold text-dark">Drag & Drop CSV File Here</h6>
                    <p class="text-muted mb-3">or click to browse files</p>
                    <button type="button" class="btn btn-outline-primary" id="browseCsvBtn">
                        <i class="bi bi-folder2-open me-2"></i>Browse Files
                    </button>
                    <input type="file" id="csv_file" name="csv_file" accept=".csv" class="d-none">
                </div>
                <div id="selectedFileInfo" class="mt-3 d-none">
                    <div class="alert alert-info">
                        <i class="bi bi-file-earmark-text me-2"></i>
                        <strong>Selected:</strong> <span id="fileName"></span>
                        <span class="badge bg-secondary ms-2" id="fileSize"></span>
                    </div>
                </div>
            </div>

            <!-- Import Options -->
            <div class="mb-4">
                <h6 class="fw-semibold mb-3">Import Options</h6>
                <div class="row">
                    <div class="col-md-6">
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" id="updateExisting" name="update_existing" checked>
                            <label class="form-check-label" for="updateExisting">
                                Update existing products (by SKU)
                            </label>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" id="skipInvalid" name="skip_invalid" checked>
                            <label class="form-check-label" for="skipInvalid">
                                Skip invalid rows
                            </label>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Import Button -->
            <div class="d-grid gap-2 d-md-flex justify-content-md-end">
                <button type="button" class="btn btn-outline-secondary me-md-2" id="clearForm">
                    <i class="bi bi-x-circle me-2"></i>Clear
                </button>
                <button type="submit" class="btn btn-primary" id="importBtn">
                    <i class="bi bi-upload me-2"></i>Import Products
                </button>
            </div>
        </form>

        <!-- Progress Section -->
        <div id="importProgress" class="mt-4 d-none">
            <div class="card border-info">
                <div class="card-body">
                    <h6 class="card-title">
                        <i class="bi bi-hourglass-split me-2"></i>Import in Progress
                    </h6>
                    <div class="progress mb-3" style="height: 25px;">
                        <div id="progressBar" class="progress-bar progress-bar-striped progress-bar-animated"
                            role="progressbar" style="width: 0%">0%</div>
                    </div>
                    <div id="progressText" class="text-center">
                        <small class="text-muted">Preparing import...</small>
                    </div>
                </div>
            </div>
        </div>

        <!-- Results Section -->
        <div id="importResults" class="mt-4 d-none">
            <!-- Will be populated by JavaScript -->
        </div>
    </div>
</div>
