@extends('layouts.app')

@section('title', 'Import History - Laravel Bulk Import')
@section('page-id', 'import-history')

@section('header')
    <h1 class="h2 mb-0 text-white">
        <i class="bi bi-clock-history me-2"></i>
        Import History
    </h1>
    <p class="text-white-50 mb-0">View all previous import operations and their results</p>
@endsection

@section('breadcrumbs')
    <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">Dashboard</a></li>
    <li class="breadcrumb-item"><a href="{{ route('imports.index') }}">Import</a></li>
    <li class="breadcrumb-item active">History</li>
@endsection

@section('content')
    <div class="row">
        <div class="col-12">
            <div class="card border-0 shadow-sm">
                <div class="card-header bg-white d-flex justify-content-between align-items-center">
                    <h5 class="card-title mb-0">
                        <i class="bi bi-table me-2"></i>
                        Import History
                    </h5>
                    <div class="d-flex gap-2">
                        <button class="btn btn-sm btn-outline-secondary" onclick="location.reload()">
                            <i class="bi bi-arrow-clockwise me-1"></i>Refresh
                        </button>
                        <a href="{{ route('imports.index') }}" class="btn btn-sm btn-primary">
                            <i class="bi bi-plus-lg me-1"></i>New Import
                        </a>
                    </div>
                </div>
                <div class="card-body p-0">
                    @if($imports->count() > 0)
                        <div class="table-responsive">
                            <table class="table table-hover mb-0">
                                <thead class="table-light">
                                    <tr>
                                        <th style="width: 80px;">ID</th>
                                        <th>Filename</th>
                                        <th style="width: 120px;">Status</th>
                                        <th style="width: 100px;">Total Rows</th>
                                        <th style="width: 100px;">Imported</th>
                                        <th style="width: 100px;">Updated</th>
                                        <th style="width: 100px;">Invalid</th>
                                        <th style="width: 100px;">Duplicates</th>
                                        <th style="width: 120px;">Success Rate</th>
                                        <th style="width: 140px;">Date</th>
                                        <th style="width: 80px;">Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($imports as $import)
                                        @php
                                            $totalProcessed = $import->imported_rows + $import->updated_rows + $import->invalid_rows + $import->duplicate_rows;
                                            $successfulRows = $import->imported_rows + $import->updated_rows;
                                            $successRate = $import->total_rows > 0 ? round($successfulRows / $import->total_rows * 100, 1) : 0;
                                        @endphp
                                        <tr>
                                            <td>
                                                <span class="badge bg-light text-dark fw-normal">#{{ $import->id }}</span>
                                            </td>
                                            <td>
                                                <div class="d-flex align-items-center">
                                                    <i class="bi bi-file-earmark-text me-2 text-muted"></i>
                                                    <div>
                                                        <div class="fw-medium text-truncate" style="max-width: 250px;" title="{{ $import->filename }}">
                                                            {{ $import->filename }}
                                                        </div>
                                                        @if($import->errors && count($import->errors) > 0)
                                                            <small class="text-danger">
                                                                <i class="bi bi-exclamation-triangle me-1"></i>
                                                                {{ count($import->errors) }} error(s)
                                                            </small>
                                                        @endif
                                                    </div>
                                                </div>
                                            </td>
                                            <td>
                                                @switch($import->status)
                                                    @case('completed')
                                                        <span class="badge bg-success">
                                                            <i class="bi bi-check-circle me-1"></i>Completed
                                                        </span>
                                                        @break
                                                    @case('processing')
                                                        <span class="badge bg-primary">
                                                            <i class="bi bi-hourglass-split me-1"></i>Processing
                                                        </span>
                                                        @break
                                                    @case('queued')
                                                        <span class="badge bg-info">
                                                            <i class="bi bi-clock me-1"></i>Queued
                                                        </span>
                                                        @break
                                                    @case('failed')
                                                        <span class="badge bg-danger">
                                                            <i class="bi bi-x-circle me-1"></i>Failed
                                                        </span>
                                                        @break
                                                    @default
                                                        <span class="badge bg-secondary">{{ ucfirst($import->status) }}</span>
                                                @endswitch
                                            </td>
                                            <td>
                                                <span class="fw-medium">{{ number_format($import->total_rows) }}</span>
                                            </td>
                                            <td>
                                                <span class="text-success fw-medium">{{ number_format($import->imported_rows) }}</span>
                                            </td>
                                            <td>
                                                <span class="text-info fw-medium">{{ number_format($import->updated_rows) }}</span>
                                            </td>
                                            <td>
                                                <span class="text-warning fw-medium">{{ number_format($import->invalid_rows) }}</span>
                                            </td>
                                            <td>
                                                <span class="text-muted fw-medium">{{ number_format($import->duplicate_rows) }}</span>
                                            </td>
                                            <td>
                                                @php
                                                    $rateClass = $successRate >= 90 ? 'text-success' : ($successRate >= 70 ? 'text-warning' : 'text-danger');
                                                @endphp
                                                <span class="fw-medium {{ $rateClass }}">{{ $successRate }}%</span>
                                            </td>
                                            <td>
                                                <div class="text-muted small">
                                                    <div>{{ $import->created_at->format('M j, Y') }}</div>
                                                    <div>{{ $import->created_at->format('g:i A') }}</div>
                                                </div>
                                            </td>
                                            <td>
                                                <div class="btn-group btn-group-sm">
                                                    @if($import->errors && count($import->errors) > 0)
                                                        <button class="btn btn-outline-danger"
                                                                data-bs-toggle="modal"
                                                                data-bs-target="#errorsModal"
                                                                data-import-id="{{ $import->id }}"
                                                                data-import-errors="{{ json_encode($import->errors) }}"
                                                                onclick="showErrors(this.dataset.importId, JSON.parse(this.dataset.importErrors))"
                                                                title="View Errors">
                                                            <i class="bi bi-exclamation-triangle"></i>
                                                        </button>
                                                    @endif
                                                </div>
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>

                        @if($imports->hasPages())
                            <div class="card-footer bg-white">
                                {{ $imports->links() }}
                            </div>
                        @endif
                    @else
                        <div class="text-center py-5">
                            <i class="bi bi-inbox fs-1 text-muted opacity-50 d-block mb-3"></i>
                            <h5 class="text-muted">No Import History Found</h5>
                            <p class="text-muted mb-4">You haven't performed any imports yet.</p>
                            <a href="{{ route('imports.index') }}" class="btn btn-primary">
                                <i class="bi bi-upload me-2"></i>Start Your First Import
                            </a>
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>

    <!-- Errors Modal -->
    <div class="modal fade" id="errorsModal" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">
                        <i class="bi bi-exclamation-triangle text-danger me-2"></i>
                        Import Errors - <span id="errorImportId">#--</span>
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div id="errorsList">
                        <!-- Errors will be loaded here -->
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
    <script>
        function showErrors(importId, errors) {
            document.getElementById('errorImportId').textContent = '#' + importId;

            const errorsList = document.getElementById('errorsList');

            if (!errors || errors.length === 0) {
                errorsList.innerHTML = '<p class="text-muted">No errors found.</p>';
                return;
            }

            let html = '<div class="list-group">';
            errors.forEach((error, index) => {
                html += `
                    <div class="list-group-item">
                        <div class="d-flex w-100 justify-content-between">
                            <h6 class="mb-1">Row ${error.row || (index + 1)}</h6>
                            <small class="text-danger">Error</small>
                        </div>
                        <div class="mb-2">
                            <strong>Issues:</strong>
                            <ul class="mb-1">
                `;

                if (error.errors) {
                    Object.entries(error.errors).forEach(([field, messages]) => {
                        if (Array.isArray(messages)) {
                            messages.forEach(message => {
                                html += `<li class="text-danger">${field}: ${message}</li>`;
                            });
                        } else {
                            html += `<li class="text-danger">${field}: ${messages}</li>`;
                        }
                    });
                } else if (error.message) {
                    html += `<li class="text-danger">${error.message}</li>`;
                }

                html += '</ul></div>';

                if (error.data) {
                    html += '<small class="text-muted"><strong>Data:</strong> ';
                    html += Object.entries(error.data).map(([key, value]) => `${key}: ${value}`).join(', ');
                    html += '</small>';
                }

                html += '</div>';
            });
            html += '</div>';

            errorsList.innerHTML = html;
        }
    </script>
@endsection
