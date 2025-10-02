@extends('layouts.app')

@section('title', 'Products - Laravel Bulk Import')
@section('page-id', 'products-index')

@section('header')
    <div class="d-flex justify-content-between align-items-center">
        <div>
            <h1 class="h2 mb-0 text-white">
                <i class="bi bi-box me-2"></i>
                Products
            </h1>
            <p class="text-white-50 mb-0">Manage your product catalog</p>
        </div>
        <div>
            <a href="{{ route('imports.index') }}" class="btn btn-outline-light">
                <i class="bi bi-upload me-2"></i>Import Products
            </a>
        </div>
    </div>
@endsection

@section('breadcrumbs')
    <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">Dashboard</a></li>
    <li class="breadcrumb-item active">Products</li>
@endsection

@section('content')
    <!-- Filters -->
    <div class="card border-0 shadow-sm mb-4">
        <div class="card-body">
            <form id="product-filters" class="row g-3">
                <div class="col-md-3">
                    <label for="filter-search" class="form-label">Search</label>
                    <input type="text" class="form-control" id="filter-search" placeholder="Search products...">
                </div>
                <div class="col-md-2">
                    <label for="filter-category" class="form-label">Category</label>
                    <select class="form-select" id="filter-category">
                        <option value="">All Categories</option>
                        <option value="Electronics">Electronics</option>
                        <option value="Books">Books</option>
                        <option value="Clothing">Clothing</option>
                        <option value="Home">Home</option>
                        <option value="Sports">Sports</option>
                    </select>
                </div>
                <div class="col-md-2">
                    <label for="filter-status" class="form-label">Status</label>
                    <select class="form-select" id="filter-status">
                        <option value="">All Status</option>
                        <option value="1">Active</option>
                        <option value="0">Inactive</option>
                    </select>
                </div>
                <div class="col-md-2">
                    <label for="filter-price-min" class="form-label">Min Price</label>
                    <input type="number" class="form-control" id="filter-price-min" placeholder="0.00" step="0.01" min="0">
                </div>
                <div class="col-md-2">
                    <label for="filter-price-max" class="form-label">Max Price</label>
                    <input type="number" class="form-control" id="filter-price-max" placeholder="999.99" step="0.01" min="0">
                </div>
                <div class="col-md-1">
                    <label class="form-label">&nbsp;</label>
                    <button type="button" class="btn btn-outline-secondary d-block" id="clear-filters">
                        <i class="bi bi-x-lg"></i>
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- Products Table -->
    <div class="card border-0 shadow-sm">
        <div class="card-header bg-white">
            <h5 class="card-title mb-0">
                <i class="bi bi-table me-2"></i>
                Products List
            </h5>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table id="products-table" class="table table-hover mb-0">
                    <thead class="table-light">
                        <tr>
                            <th width="80">Image</th>
                            <th>SKU</th>
                            <th>Name</th>
                            <th>Category</th>
                            <th width="100">Price</th>
                            <th width="80">Stock</th>
                            <th width="80">Status</th>
                            <th width="120">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <!-- DataTable will populate this -->
                    </tbody>
                </table>
            </div>
        </div>
    </div>
@endsection

@section('page-scripts')
    @vite(['resources/js/pages/products.js'])
@endsection
