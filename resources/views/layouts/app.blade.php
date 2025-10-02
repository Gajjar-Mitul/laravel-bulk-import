<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>@yield('title', 'Laravel Bulk Import System')</title>

     <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">

    <!-- Bootstrap Icons -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css" rel="stylesheet">

    <!-- Custom Styles -->
    @vite(['resources/css/app.css'])
    @stack('styles')
</head>
<body data-page="@yield('page-id')" class="bg-gray-50">
    <div id="app">
        <!-- Navigation -->
        @include('components.shared.navigation')

        <!-- Page Header -->
        @hasSection('header')
            <div class="page-header">
                <div class="container">
                    <div class="row">
                        <div class="col-12">
                            @yield('header')
                        </div>
                    </div>
                </div>
            </div>
        @endif

        <!-- Main Content -->
        <div class="content-wrapper">
            <div class="container">
                <!-- Flash Messages -->
                @include('components.shared.flash-messages')

                <!-- Breadcrumbs -->
                @hasSection('breadcrumbs')
                    <nav aria-label="breadcrumb" class="mb-4">
                        <ol class="breadcrumb">
                            @yield('breadcrumbs')
                        </ol>
                    </nav>
                @endif

                <!-- Page Content -->
                @yield('content')
            </div>
        </div>

        <!-- Footer -->
        @include('components.shared.footer')
    </div>

    <!-- Bootstrap JavaScript -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>

    <!-- Scripts -->
    @vite(['resources/js/app.js'])
    @stack('scripts')

    <!-- Page-specific scripts -->
    @hasSection('page-scripts')
        @yield('page-scripts')
    @endif
</body>
</html>
