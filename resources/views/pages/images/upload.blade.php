@extends('layouts.app')

@section('title', 'Upload Images')
@section('page-id', 'image-upload')

@section('header')
    <h1 class="h2 mb-0 text-white">
        <i class="bi bi-images me-2"></i>Upload Images
    </h1>
@endsection

@section('content')
    <div class="row">
        <div class="col-lg-8">
            <div class="card border-0 shadow-sm">
                <div class="card-header bg-white">
                    <h5 class="mb-0">Chunked Image Upload</h5>
                </div>
                <div class="card-body">
                    <!-- Drop Zone -->
                    <div id="imageDropZone" class="border-2 border-dashed border-primary rounded p-5 text-center mb-4">
                        <i class="bi bi-cloud-upload display-1 text-primary mb-3"></i>
                        <h4>Drag & Drop Images Here</h4>
                        <input type="file" id="imageFileInput" multiple accept="image/*" class="d-none">
                        <button type="button" class="btn btn-primary" id="browseImagesBtn">Browse Images</button>
                    </div>

                    <!-- Selected Files -->
                    <div id="selectedFilesContainer" class="d-none">
                        <div id="selectedFilesList" class="mb-4"></div>
                        <button type="button" class="btn btn-success" id="startUploadBtn">Start Upload</button>
                    </div>

                    <!-- Progress -->
                    <div id="uploadProgressContainer" class="d-none mt-4">
                        <div id="uploadProgressList"></div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-lg-4">
            <div class="card border-0 shadow-sm">
                <div class="card-header bg-light">
                    <h6 class="mb-0">Upload Info</h6>
                </div>
                <div class="card-body">
                    <ul class="list-unstyled">
                        <li><i class="bi bi-check text-success me-2"></i>Chunked upload</li>
                        <li><i class="bi bi-check text-success me-2"></i>Auto retry</li>
                        <li><i class="bi bi-check text-success me-2"></i>4 variants generated</li>
                    </ul>
                </div>
            </div>
        </div>
    </div>
@endsection

@section("page-scripts")
    <script type="module" src="{{ asset("js/classes/ChunkedImageUploader.js") }}"></script>
@endsection
