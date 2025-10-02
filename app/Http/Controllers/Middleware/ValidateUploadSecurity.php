<?php

// app/Http/Middleware/ValidateUploadSecurity.php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class ValidateUploadSecurity
{
    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next): Response
    {
        // Rate limiting for uploads
        // You can add rate limiting logic here
        // For example, using Laravel's built-in rate limiter
        // Validate file types more strictly
        if ($request->is('api/uploads/*') && $request->hasFile('chunk_file')) {
            $file = $request->file('chunk_file');
            $mimeType = $file->getMimeType();
            // Additional security checks
            if (! in_array($mimeType, ['image/jpeg', 'image/png', 'image/gif', 'image/webp'])) {
                return response()->json([
                    'success' => false,
                    'message' => 'Invalid file type',
                ], 422);
            }
        }

        return $next($request);
    }
}
