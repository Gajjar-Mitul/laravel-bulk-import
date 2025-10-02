<?php

// tests/Unit/ChunkedUploadServiceTest.php

namespace Tests\Unit;

use App\Domains\FileUploads\Models\Upload;
use App\Domains\FileUploads\Services\ChunkedUploadService;
use App\Domains\FileUploads\Services\ImageProcessingService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Override;
use Tests\TestCase;

class ChunkedUploadServiceTest extends TestCase
{
    use RefreshDatabase;

    protected ChunkedUploadService $service;

    #[Override]
    protected function setUp(): void
    {
        parent::setUp();
        $imageService = $this->createMock(ImageProcessingService::class);
        $this->service = new ChunkedUploadService($imageService);
        Storage::fake('local');
    }

    public function test_initialize_upload_creates_record(): void
    {
        $upload = $this->service->initializeUpload(
            'test-image.jpg',
            1024000,
            10,
            'image/jpeg',
            'abc123checksum'
        );

        $this->assertInstanceOf(Upload::class, $upload);
        $this->assertEquals('test-image.jpg', $upload->original_filename);
        $this->assertEquals(1024000, $upload->total_size);
        $this->assertEquals(10, $upload->total_chunks);
        $this->assertEquals('image/jpeg', $upload->mime_type);
        $this->assertEquals('abc123checksum', $upload->checksum);
        $this->assertEquals('pending', $upload->status);
        $this->assertNotNull($upload->uuid);
    }

    public function test_upload_chunk_stores_file(): void
    {
        $upload = $this->service->initializeUpload('test.jpg', 2048, 2, 'image/jpeg', 'dummy_checksum');

        // Create fake chunk file
        $chunkContent = str_repeat('a', 1024);
        $chunkFile = UploadedFile::fake()->createWithContent('chunk_0', $chunkContent);

        $result = $this->service->uploadChunk($upload, $chunkFile, 0, 1024);

        $this->assertTrue($result);

        // Refresh upload
        $upload = $upload->fresh();
        $this->assertEquals(1, $upload->uploaded_chunks);
        $this->assertEquals('uploading', $upload->status);
        $this->assertTrue($upload->isChunkUploaded(0));
    }

    public function test_upload_chunk_is_idempotent(): void
    {
        $chunkContent = str_repeat('b', 1024);
        $checksum = hash('sha256', $chunkContent);
        $upload = $this->service->initializeUpload('test.jpg', 1024, 1, 'image/jpeg', $checksum);

        $chunkFile1 = UploadedFile::fake()->createWithContent('chunk_0', $chunkContent);
        $chunkFile2 = UploadedFile::fake()->createWithContent('chunk_0', $chunkContent);

        // Upload same chunk twice
        $result1 = $this->service->uploadChunk($upload, $chunkFile1, 0, 1024);
        $result2 = $this->service->uploadChunk($upload, $chunkFile2, 0, 1024);

        $this->assertTrue($result1);
        $this->assertTrue($result2);

        $upload = $upload->fresh();
        $this->assertEquals(1, $upload->uploaded_chunks);
    }

    public function test_upload_completes_when_all_chunks_uploaded(): void
    {
        // Create test file content
        $chunk1Content = str_repeat('a', 1024);
        $chunk2Content = str_repeat('b', 1024);
        $fullContent = $chunk1Content.$chunk2Content;
        $checksum = hash('sha256', $fullContent);

        $upload = $this->service->initializeUpload('test.jpg', 2048, 2, 'image/jpeg', $checksum);

        $chunkFile1 = UploadedFile::fake()->createWithContent('chunk_0', $chunk1Content);
        $chunkFile2 = UploadedFile::fake()->createWithContent('chunk_1', $chunk2Content);

        $this->service->uploadChunk($upload, $chunkFile1, 0, 1024);
        $this->service->uploadChunk($upload, $chunkFile2, 1, 1024);

        $upload = $upload->fresh();
        $this->assertEquals('completed', $upload->status);
        $this->assertEquals(2, $upload->uploaded_chunks);
    }

    public function test_get_upload_status_returns_correct_upload(): void
    {
        $upload = $this->service->initializeUpload('test.jpg', 1024, 1, 'image/jpeg', 'dummy_checksum');

        $retrieved = $this->service->getUploadStatus($upload->uuid);

        $this->assertInstanceOf(Upload::class, $retrieved);
        $this->assertEquals($upload->uuid, $retrieved->uuid);
    }

    public function test_cancel_upload_cleans_up_files_and_record(): void
    {
        $chunkContent = str_repeat('a', 1024);
        $checksum = hash('sha256', $chunkContent);
        $upload = $this->service->initializeUpload('test.jpg', 1024, 1, 'image/jpeg', $checksum);
        $uuid = $upload->uuid;

        // Upload a chunk first
        $chunk = UploadedFile::fake()->createWithContent('chunk_0', $chunkContent);
        $this->service->uploadChunk($upload, $chunk, 0, 1024);

        $result = $this->service->cancelUpload($uuid);

        $this->assertTrue($result);
        $this->assertDatabaseMissing('uploads', [
            'uuid' => $uuid,
        ]);
    }
}
