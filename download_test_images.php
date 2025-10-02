<?php

/**
 * Download Sample Images for Testing
 * Downloads real images from various sources for testing
 */

require_once __DIR__.'/vendor/autoload.php';

class SampleImageDownloader
{
    private string $downloadDir;

    private array $imageSources = [
        'picsum' => 'https://picsum.photos/{width}/{height}?random={id}',
        'placeholder' => 'https://via.placeholder.com/{width}x{height}/random?text=Test+{id}',
        'unsplash' => 'https://source.unsplash.com/{width}x{height}/?random&sig={id}',
    ];

    public function __construct()
    {
        $this->downloadDir = __DIR__.'/storage/test_images';
        $this->createDownloadDir();
    }

    /**
     * Download specified number of sample images
     */
    public function downloadSampleImages(int $count = 200): array
    {
        echo "📥 Downloading {$count} sample images to {$this->downloadDir}\n\n";

        $downloaded = [];
        $failed = 0;

        for ($i = 1; $i <= $count; $i++) {
            try {
                echo "📷 Downloading image {$i}/{$count}... ";

                $imageInfo = $this->downloadRandomImage($i);
                if ($imageInfo) {
                    $downloaded[] = $imageInfo;
                    echo "✅ Downloaded: {$imageInfo['filename']} ({$this->formatBytes($imageInfo['size'])})\n";
                } else {
                    $failed++;
                    echo "❌ Failed\n";
                }

                // Small delay to avoid rate limiting
                usleep(100000); // 0.1 second

            } catch (Exception $e) {
                echo "💥 Error: {$e->getMessage()}\n";
                $failed++;
            }
        }

        echo "\n📊 Downloaded: ".count($downloaded)." | Failed: {$failed}\n";

        return $downloaded;
    }

    /**
     * Download a single random image
     */
    private function downloadRandomImage(int $id): ?array
    {
        $source = array_rand($this->imageSources);
        $sizes = [
            ['width' => 400, 'height' => 300],
            ['width' => 800, 'height' => 600],
            ['width' => 1024, 'height' => 768],
            ['width' => 1920, 'height' => 1080],
        ];

        $size = $sizes[array_rand($sizes)];
        $url = str_replace(
            ['{width}', '{height}', '{id}'],
            [$size['width'], $size['height'], $id],
            $this->imageSources[$source]
        );

        $filename = "sample_{$id}_{$size['width']}x{$size['height']}.jpg";
        $filepath = $this->downloadDir.'/'.$filename;

        $context = stream_context_create([
            'http' => [
                'timeout' => 30,
                'user_agent' => 'Laravel Bulk Upload Tester/1.0',
            ],
        ]);

        $imageData = @file_get_contents($url, false, $context);
        if ($imageData === false) {
            return null;
        }

        file_put_contents($filepath, $imageData);

        return [
            'filename' => $filename,
            'filepath' => $filepath,
            'size' => filesize($filepath),
            'source' => $source,
            'url' => $url,
        ];
    }

    /**
     * Create upload test from downloaded images
     */
    public function testUploadFromDownloaded(): void
    {
        $images = glob($this->downloadDir.'/*.jpg');

        if (empty($images)) {
            echo "❌ No images found in {$this->downloadDir}. Run downloadSampleImages() first.\n";

            return;
        }

        echo '🚀 Testing upload with '.count($images)." downloaded images\n\n";

        $successful = 0;
        $failed = 0;

        foreach ($images as $imagePath) {
            $filename = basename($imagePath);
            echo "⬆️  Uploading {$filename}... ";

            try {
                $result = $this->uploadImage($imagePath);
                if ($result['success']) {
                    echo "✅ Success (UUID: {$result['uuid']})\n";
                    $successful++;
                } else {
                    echo "❌ Failed: {$result['error']}\n";
                    $failed++;
                }
            } catch (Exception $e) {
                echo "💥 Exception: {$e->getMessage()}\n";
                $failed++;
            }
        }

        echo "\n📊 Upload Results: ✅ {$successful} | ❌ {$failed}\n";
    }

    /**
     * Simple upload test (for downloaded images)
     */
    private function uploadImage(string $filePath): array
    {
        $filename = basename($filePath);
        $fileSize = filesize($filePath);
        $checksum = hash_file('sha256', $filePath);

        // For simplicity, we'll use a simple POST to test API
        $ch = curl_init();
        curl_setopt_array($ch, [
            CURLOPT_URL => 'https://laravel-bulk-import.local/api/uploads/initialize',
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => [
                'filename' => $filename,
                'total_size' => $fileSize,
                'total_chunks' => 1, // Single chunk for simplicity
                'mime_type' => 'image/jpeg',
                'checksum' => $checksum,
            ],
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_SSL_VERIFYPEER => false,
            CURLOPT_HTTPHEADER => ['Accept: application/json'],
        ]);

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($httpCode !== 200) {
            return ['success' => false, 'error' => "HTTP {$httpCode}"];
        }

        $result = json_decode($response, true);

        return [
            'success' => $result['success'] ?? false,
            'uuid' => $result['data']['upload_uuid'] ?? null,
            'error' => $result['message'] ?? 'Unknown error',
        ];
    }

    private function createDownloadDir(): void
    {
        if (! is_dir($this->downloadDir)) {
            mkdir($this->downloadDir, 0755, true);
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
}

// CLI Usage
if (php_sapi_name() === 'cli') {
    $downloader = new SampleImageDownloader;

    echo "Choose option:\n";
    echo "1. Download sample images\n";
    echo "2. Test upload with existing images\n";
    echo "3. Download and test upload\n";
    echo 'Enter choice (1-3): ';

    $choice = (int) trim(fgets(STDIN));

    switch ($choice) {
        case 1:
            echo 'How many images to download? (default: 50): ';
            $count = (int) trim(fgets(STDIN)) ?: 50;
            $downloader->downloadSampleImages($count);
            break;

        case 2:
            $downloader->testUploadFromDownloaded();
            break;

        case 3:
            echo 'How many images to download and test? (default: 20): ';
            $count = (int) trim(fgets(STDIN)) ?: 20;
            $downloader->downloadSampleImages($count);
            echo "\n".str_repeat('-', 40)."\n";
            $downloader->testUploadFromDownloaded();
            break;

        default:
            echo "Invalid choice\n";
    }
}
