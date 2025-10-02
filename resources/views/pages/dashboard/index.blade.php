@extends('layouts.app')

@section('title', 'Dashboard - Laravel Bulk Import')
@section('page-id', 'dashboard')

@section('header')
    <h1 class="h2 mb-0 text-white">
        <i class="bi bi-speedometer2 me-2"></i>
        Dashboard
    </h1>
    <p class="text-white-50 mb-0">Overview of your bulk import system</p>
@endsection

@section('breadcrumbs')
    <li class="breadcrumb-item active">Dashboard</li>
@endsection

@section('content')
    <!-- Stats Cards Row -->
    <div class="row g-4 mb-4">
        <div class="col-xl-3 col-md-6">
            <div class="card border-0 shadow-sm">
                <div class="card-body">
                    <div class="d-flex align-items-center">
                        <div class="flex-shrink-0">
                            <div class="bg-primary bg-gradient p-3 rounded">
                                <i class="bi bi-box text-white fs-4"></i>
                            </div>
                        </div>
                        <div class="flex-grow-1 ms-3">
                            <h6 class="text-muted mb-1">Total Products</h6>
                            <h3 class="mb-0" id="total-products">--</h3>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-xl-3 col-md-6">
            <div class="card border-0 shadow-sm">
                <div class="card-body">
                    <div class="d-flex align-items-center">
                        <div class="flex-shrink-0">
                            <div class="bg-success bg-gradient p-3 rounded">
                                <i class="bi bi-upload text-white fs-4"></i>
                            </div>
                        </div>
                        <div class="flex-grow-1 ms-3">
                            <h6 class="text-muted mb-1">Successful Imports</h6>
                            <h3 class="mb-0" id="successful-imports">--</h3>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-xl-3 col-md-6">
            <div class="card border-0 shadow-sm">
                <div class="card-body">
                    <div class="d-flex align-items-center">
                        <div class="flex-shrink-0">
                            <div class="bg-info bg-gradient p-3 rounded">
                                <i class="bi bi-images text-white fs-4"></i>
                            </div>
                        </div>
                        <div class="flex-grow-1 ms-3">
                            <h6 class="text-muted mb-1">Total Images</h6>
                            <h3 class="mb-0" id="total-images">--</h3>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-xl-3 col-md-6">
            <div class="card border-0 shadow-sm">
                <div class="card-body">
                    <div class="d-flex align-items-center">
                        <div class="flex-shrink-0">
                            <div class="bg-warning bg-gradient p-3 rounded">
                                <i class="bi bi-clock-history text-white fs-4"></i>
                            </div>
                        </div>
                        <div class="flex-grow-1 ms-3">
                            <h6 class="text-muted mb-1">Pending Uploads</h6>
                            <h3 class="mb-0" id="pending-uploads">--</h3>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Charts Row -->
    <div class="row g-4">
        <div class="col-lg-8">
            <div class="card border-0 shadow-sm">
                <div class="card-header bg-white">
                    <h5 class="card-title mb-0">
                        <i class="bi bi-bar-chart me-2"></i>
                        Import Activity (Last 30 Days)
                    </h5>
                </div>
                <div class="card-body">
                    <canvas id="importChart" height="300"></canvas>
                </div>
            </div>
        </div>

        <div class="col-lg-4">
            <div class="card border-0 shadow-sm">
                <div class="card-header bg-white">
                    <h5 class="card-title mb-0">
                        <i class="bi bi-pie-chart me-2"></i>
                        Import Success Rate
                    </h5>
                </div>
                <div class="card-body">
                    <canvas id="successChart" height="300"></canvas>
                </div>
            </div>
        </div>
    </div>

    <!-- Recent Activity -->
    <div class="row mt-4">
        <div class="col-12">
            <div class="card border-0 shadow-sm">
                <div class="card-header bg-white d-flex justify-content-between align-items-center">
                    <h5 class="card-title mb-0">
                        <i class="bi bi-clock-history me-2"></i>
                        Recent Import Activity
                    </h5>
                    <a href="{{ route('imports.history') }}" class="btn btn-sm btn-outline-primary">
                        View All
                    </a>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-hover mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th>Import ID</th>
                                    <th>Filename</th>
                                    <th>Status</th>
                                    <th>Rows Processed</th>
                                    <th>Success Rate</th>
                                    <th>Date</th>
                                </tr>
                            </thead>
                            <tbody id="recent-imports">
                                <tr>
                                    <td colspan="6" class="text-center py-4">
                                        <div class="spinner-border spinner-border-sm me-2"></div>
                                        Loading recent imports...
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection

@push('styles')
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
@endpush

@section('page-scripts')
    @if(app()->environment('testing'))
        <!-- Skip Vite in testing environment -->
        <script>
            // Basic dashboard functionality for testing
            document.addEventListener('DOMContentLoaded', function() {
                // Set default values for testing
                document.getElementById('total-products').textContent = '0';
                document.getElementById('successful-imports').textContent = '0';
                document.getElementById('total-images').textContent = '0';
                document.getElementById('pending-uploads').textContent = '0';
            });
        </script>
    @else
        @vite(['resources/js/pages/dashboard.js'])
    @endif
@endsection
