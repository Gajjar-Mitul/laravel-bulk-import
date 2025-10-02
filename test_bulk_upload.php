<?php

/**
 * Bulk Image Upload Test Script
 * Generates mock images and uploads them via chunked upload API
 */

require_once __DIR__.'/vendor/autoload.php';

class BulkImageUploadTester
{
    private string $baseUrl;

    private string $tempDir;

    private array $imageTypes = ['jpeg', 'png', 'gif', 'webp'];

    private array $imageSizes = [
        'small' => [400, 300],
        'medium' => [800, 600],
        'large' => [1920, 1080],
        'huge' => [4096, 3072],
    ];

    public function __construct(string $baseUrl = 'http://localhost:8000')
    {
        $this->baseUrl = rtrim($baseUrl, '/');
        $this->tempDir = sys_get_temp_dir().'/bulk_upload_test';
        $this->createTempDir();
    }

    /**
     * Generate and upload specified number of test images
     */
    public function testBulkUpload(int $imageCount = 200, bool $attachToProducts = true): void
    {
        echo "🚀 Starting bulk upload test with {$imageCount} images\n";
        echo "📁 Temp directory: {$this->tempDir}\n\n";

        $successful = 0;
        $failed = 0;
        $startTime = microtime(true);

        for ($i = 1; $i <= $imageCount; $i++) {
            try {
                echo "📷 Generating image {$i}/{$imageCount}... ";

                // Generate random test image
                $imagePath = $this->generateTestImage($i);
                echo "✅ Generated ({$this->formatBytes(filesize($imagePath))})\n";

                // Upload via chunked API
                echo '⬆️  Uploading... ';
                $uploadResult = $this->uploadImageChunked($imagePath, "test_image_{$i}.jpg");

                if ($uploadResult['success']) {
                    echo "✅ Uploaded (UUID: {$uploadResult['uuid']})\n";

                    // Optionally attach to random product
                    if ($attachToProducts && rand(1, 3) === 1) { // 33% chance
                        $this->attachToRandomProduct($uploadResult['uuid']);
                    }

                    $successful++;
                } else {
                    echo "❌ Failed: {$uploadResult['error']}\n";
                    $failed++;
                }

                // Cleanup temp file
                unlink($imagePath);

                // Progress update every 10 images
                if ($i % 10 === 0) {
                    $elapsed = microtime(true) - $startTime;
                    $rate = $i / $elapsed;
                    echo "\n📊 Progress: {$i}/{$imageCount} | Rate: ".number_format($rate, 1)." images/sec\n\n";
                }

            } catch (Exception $e) {
                echo "💥 Exception: {$e->getMessage()}\n";
                $failed++;
            }
        }

        $totalTime = microtime(true) - $startTime;
        $this->printSummary($successful, $failed, $totalTime);
        $this->cleanup();
    }

    /**
     * Generate a test image with random properties
     */
    private function generateTestImage(int $index): string
    {
        // Random image properties
        $type = $this->imageTypes[array_rand($this->imageTypes)];
        $sizeKey = array_rand($this->imageSizes);
        [$width, $height] = $this->imageSizes[$sizeKey];

        // Add some randomness to dimensions
        $width += rand(-100, 100);
        $height += rand(-75, 75);

        $filename = "test_image_{$index}_{$sizeKey}.{$type}";
        $filepath = $this->tempDir.'/'.$filename;

        // Create image using GD
        $image = imagecreatetruecolor($width, $height);

        // Random background color
        $bgColor = imagecolorallocate($image, rand(0, 255), rand(0, 255), rand(0, 255));
        imagefill($image, 0, 0, $bgColor);

        // Add some random shapes and text
        $this->addRandomShapes($image, $width, $height);
        $this->addImageText($image, $index, $sizeKey);

        // Save image
        switch ($type) {
            case 'jpeg':
                imagejpeg($image, $filepath, rand(70, 95));
                break;
            case 'png':
                imagepng($image, $filepath, rand(1, 9));
                break;
            case 'gif':
                imagegif($image, $filepath);
                break;
            case 'webp':
                imagewebp($image, $filepath, rand(70, 95));
                break;
        }

        imagedestroy($image);

        return $filepath;
    }

    /**
     * Add random shapes to make images more interesting
     */
    private function addRandomShapes($image, int $width, int $height): void
    {
        $shapeCount = rand(5, 15);

        for ($i = 0; $i < $shapeCount; $i++) {
            $color = imagecolorallocate($image, rand(0, 255), rand(0, 255), rand(0, 255));
            $shapeType = rand(1, 3);

            switch ($shapeType) {
                case 1: // Rectangle
                    imagerectangle($image,
                        rand(0, $width / 2), rand(0, $height / 2),
                        rand($width / 2, $width), rand($height / 2, $height),
                        $color
                    );
                    break;
                case 2: // Circle
                    imageellipse($image,
                        rand(0, $width), rand(0, $height),
                        rand(20, 200), rand(20, 200),
                        $color
                    );
                    break;
                case 3: // Line
                    imageline($image,
                        rand(0, $width), rand(0, $height),
                        rand(0, $width), rand(0, $height),
                        $color
                    );
                    break;
            }
        }
    }

    /**
     * Add text to image for identification
     */
    private function addImageText($image, int $index, string $size): void
    {
        $white = imagecolorallocate($image, 255, 255, 255);
        $black = imagecolorallocate($image, 0, 0, 0);

        $text = "Test Image #{$index} ({$size})";
        $date = date('Y-m-d H:i:s');

        // Add text with black outline for visibility
        imagestring($image, 5, 12, 12, $text, $black);
        imagestring($image, 5, 10, 10, $text, $white);

        imagestring($image, 3, 12, 42, $date, $black);
        imagestring($image, 3, 10, 40, $date, $white);
    }

    /**
     * Upload image using chunked upload API
     */
    private function uploadImageChunked(string $filePath, string $filename): array
    {
        $fileSize = filesize($filePath);
        $checksum = hash_file('sha256', $filePath);
        $chunkSize = 1024 * 1024; // 1MB chunks
        $totalChunks = (int) ceil($fileSize / $chunkSize);

        // Step 1: Initialize upload
        $initResponse = $this->apiCall('/api/uploads/initialize', [
            'filename' => $filename,
            'total_size' => $fileSize,
            'total_chunks' => $totalChunks,
            'mime_type' => 'image/jpeg',
            'checksum' => $checksum,
        ]);

        if (! $initResponse['success']) {
            return ['success' => false, 'error' => $initResponse['message'] ?? 'Init failed'];
        }

        $uploadUuid = $initResponse['data']['upload_uuid'];

        // Step 2: Upload chunks
        $handle = fopen($filePath, 'rb');
        for ($chunkIndex = 0; $chunkIndex < $totalChunks; $chunkIndex++) {
            $chunkData = fread($handle, $chunkSize);

            $chunkResponse = $this->apiCall('/api/uploads/chunk', [
                'upload_uuid' => $uploadUuid,
                'chunk_index' => $chunkIndex,
                'chunk_size' => strlen($chunkData),
            ], [
                'chunk_file' => new CURLFile('data://application/octet-stream;base64,'.base64_encode($chunkData), 'application/octet-stream', "chunk_{$chunkIndex}"),
            ]);

            if (! $chunkResponse['success']) {
                fclose($handle);

                return ['success' => false, 'error' => "Chunk {$chunkIndex} failed: ".($chunkResponse['message'] ?? 'Unknown error')];
            }
        }
        fclose($handle);

        return ['success' => true, 'uuid' => $uploadUuid];
    }

    /**
     * Attach image to a random product
     */
    private function attachToRandomProduct(string $uploadUuid): void
    {
        // Get random product
        $productsResponse = $this->apiCall('/api/products', [], [], 'GET');
        if (! empty($productsResponse['data'])) {
            $randomProduct = $productsResponse['data'][array_rand($productsResponse['data'])];

            $this->apiCall('/api/uploads/attach-to-product', [
                'upload_uuid' => $uploadUuid,
                'product_sku' => $randomProduct['sku'],
                'is_primary' => rand(1, 5) === 1, // 20% chance of being primary
            ]);

            echo "🔗 Attached to product {$randomProduct['sku']} ";
        }
    }

    /**
     * Make API call
     */
    private function apiCall(string $endpoint, array $data = [], array $files = [], string $method = 'POST'): array
    {
        $ch = curl_init();
        $url = $this->baseUrl.$endpoint;

        $headers = [
            'Accept: application/json',
            'X-Requested-With: XMLHttpRequest',
        ];

        curl_setopt_array($ch, [
            CURLOPT_URL => $url,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_SSL_VERIFYPEER => false,
            CURLOPT_CUSTOMREQUEST => $method,
        ]);

        if ($method === 'POST' && (! empty($data) || ! empty($files))) {
            if (! empty($files)) {
                // For file uploads, use multipart/form-data
                $postData = array_merge($data, $files);
                curl_setopt($ch, CURLOPT_POSTFIELDS, $postData);
            } else {
                // For JSON data, send as JSON
                $headers[] = 'Content-Type: application/json';
                curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
            }
        }

        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $curlError = curl_error($ch);
        curl_close($ch);

        if ($response === false) {
            return ['success' => false, 'message' => 'cURL error: '.$curlError];
        }

        $decoded = json_decode($response, true);

        return $decoded ?: ['success' => false, 'message' => 'Invalid JSON response'];
    }

    /**
     * Helper methods
     */
    private function createTempDir(): void
    {
        if (! is_dir($this->tempDir)) {
            mkdir($this->tempDir, 0755, true);
        }
    }

    private function cleanup(): void
    {
        if (is_dir($this->tempDir)) {
            array_map('unlink', glob($this->tempDir.'/*'));
            rmdir($this->tempDir);
        }
    }

    private function formatBytes(int $bytes): string
    {
        $units = ['B', 'KB', 'MB', 'GB'];
        for ($i = 0; $bytes > 1024 && $i < count($units) - 1; $i++) {
            $bytes /= 1024;
        }

        return round($bytes, 2).' '.$units[$i];
    }

    private function printSummary(int $successful, int $failed, float $totalTime): void
    {
        echo "\n".str_repeat('=', 60)."\n";
        echo "📊 BULK UPLOAD TEST SUMMARY\n";
        echo str_repeat('=', 60)."\n";
        echo "✅ Successful uploads: {$successful}\n";
        echo "❌ Failed uploads: {$failed}\n";
        echo '⏱️  Total time: '.number_format($totalTime, 2)." seconds\n";
        echo '📈 Average rate: '.number_format(($successful + $failed) / $totalTime, 2)." images/sec\n";
        echo '🎯 Success rate: '.number_format(($successful / ($successful + $failed)) * 100, 1)."%\n";
        echo str_repeat('=', 60)."\n";
    }
}

// Usage examples
if (php_sapi_name() === 'cli') {
    $tester = new BulkImageUploadTester('http://localhost:8000');

    // Test with different scenarios
    echo "Select test scenario:\n";
    echo "1. Quick test (10 images)\n";
    echo "2. Medium test (50 images)\n";
    echo "3. Large test (200 images)\n";
    echo "4. Stress test (500 images)\n";
    echo 'Enter choice (1-4): ';

    $choice = (int) trim(fgets(STDIN));

    $counts = [1 => 10, 2 => 50, 3 => 200, 4 => 500];
    $count = $counts[$choice] ?? 10;

    $tester->testBulkUpload($count, true);
}
