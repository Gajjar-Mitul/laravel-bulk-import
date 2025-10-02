<?php

declare(strict_types=1);

namespace App\Domains\FileUploads\Controllers;

use App\Domains\FileUploads\Models\Upload;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class UploadController extends Controller
{
    /**
     * Display the file uploads index page
     */
    public function index(): View
    {
        return view('pages.uploads.index');
    }

    /**
     * Display the file gallery
     */
    public function gallery(Request $request): View
    {
        $query = Upload::with([
            'images' => function ($query): void {
                // Only get 'original' or first variant for display
                $query->where('variant', 'original')->orWhere('variant', '256px');
            },
        ])
            ->where('status', 'completed');

        // Apply search filter if provided
        if ($search = $request->get('search')) {
            $query->where('original_filename', 'LIKE', '%'.$search.'%');
        }

        // Apply sorting
        $sortBy = $request->get('sort', 'completed_at');
        $sortDirection = $request->get('direction', 'desc');

        $allowedSorts = ['completed_at', 'original_filename', 'total_size'];
        if (in_array($sortBy, $allowedSorts)) {
            $query->orderBy($sortBy, $sortDirection);
        } else {
            $query->orderBy('completed_at', 'desc');
        }

        $uploads = $query->paginate(24);

        return view('pages.uploads.gallery', [
            'uploads' => $uploads,
        ]);
    }

    /**
     * Serve uploaded images
     */
    public function serveImage(string $uuid, string $variant = 'original'): BinaryFileResponse
    {
        $upload = Upload::byUuid($uuid)->first();

        abort_unless($upload !== null, 404, 'Upload not found');

        // Find the specific image variant
        $image = $upload->images()->where('variant', $variant)->first();

        abort_unless($image !== null, 404, 'Image variant not found');

        // Check if file exists
        abort_unless(Storage::exists($image->storage_path), 404, 'Image file not found');

        $filePath = Storage::path($image->storage_path);
        $mimeType = $image->mime_type ?: 'application/octet-stream';

        return response()->file($filePath, [
            'Content-Type' => $mimeType,
            'Cache-Control' => 'public, max-age=3600',
        ]);
    }
}
