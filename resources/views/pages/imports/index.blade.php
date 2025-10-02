@extends('layouts.app')

@section('title', 'Import Products - Laravel Bulk Import')
@section('page-id', 'import')

@section('header')
<h1 class="h2 mb-0 text-white">
    <i class="bi bi-upload me-2"></i>
    Import Products
</h1>
<p class="text-white-50 mb-0">Upload CSV files to import products in bulk</p>
@endsection

@section('breadcrumbs')
<li class="breadcrumb-item"><a href="{{ route('dashboard') }}">Dashboard</a></li>
<li class="breadcrumb-item active">Import Products</li>
@endsection

@section('content')
<!-- Import Form -->
<div class="row">
    <div class="col-lg-8">
        @include('components.products.import-form')
    </div>
    <div class="col-lg-4">
        @include('components.products.import-instructions')
    </div>
</div>

<!-- Recent Imports -->
<div class="row mt-4">
    <div class="col-12">
        <div class="card border-0 shadow-sm">
            <div class="card-header bg-white d-flex justify-content-between align-items-center">
                <h5 class="card-title mb-0">
                    <i class="bi bi-clock-history me-2"></i>
                    Recent Imports
                </h5>
                <a href="{{ route('imports.history') }}" class="btn btn-sm btn-outline-primary">
                    View All History
                </a>
            </div>
            <div class="card-body p-0">
                @if($recentImports->count() > 0)
                <div class="table-responsive">
                    <table class="table table-hover mb-0">
                        <thead class="table-light">
                            <tr>
                                <th>ID</th>
                                <th>Filename</th>
                                <th>Status</th>
                                <th>Total Rows</th>
                                <th>Success Rate</th>
                                <th>Date</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($recentImports as $import)
                            <tr>
                                <td><span class="badge bg-secondary">{{ $import->id }}</span></td>
                                <td>
                                    <div class="fw-semibold">{{ $import->filename ?: 'Unknown' }}</div>
                                    <small class="text-muted">{{ $import->import_type ?: 'products' }}</small>
                                </td>
                                <td>
                                    @if($import->status === 'completed')
                                    <span class="badge bg-success">Completed</span>
                                    @else
                                    <span class="badge bg-warning">{{ ucfirst($import->status) }}</span>
                                    @endif
                                </td>
                                <td>{{ $import->total_rows ?: 0 }}</td>
                                <td>
                                    @php
                                    $successRate = $import->total_rows > 0
                                    ? round((($import->imported_rows + $import->updated_rows) / $import->total_rows) * 100)
                                    : 0;
                                    @endphp
                                    {{ $successRate }}%
                                </td>
                                <td>{{ $import->created_at ? $import->created_at->format('M j, Y g:i A') : 'Unknown' }}</td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
                @else
                <div class="text-center py-5">
                    <i class="bi bi-inbox display-4 text-muted"></i>
                    <h5 class="mt-3 text-muted">No imports yet</h5>
                    <p class="text-muted">Import your first CSV file to see results here</p>
                </div>
                @endif
            </div>
        </div>
    </div>
</div>
@endsection

@section('page-scripts')
@vite(['resources/js/pages/import.js'])
@endsection
