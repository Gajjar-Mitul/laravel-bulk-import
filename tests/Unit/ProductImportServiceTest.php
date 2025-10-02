<?php

// tests/Unit/ProductImportServiceTest.php

namespace Tests\Unit;

use App\Domains\Products\BulkImportQueries as ProductsBulkImportQueries;
use App\Domains\Products\DataObjects\BulkImportData;
use App\Domains\Products\Models\Product;
use App\Domains\Products\ProductQueries;
use App\Domains\Products\Services\ProductImportService;
use App\Domains\Products\Services\ProductService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Override;
use Tests\TestCase;

class ProductImportServiceTest extends TestCase
{
    use RefreshDatabase;

    protected ProductImportService $service;

    #[Override]
    protected function setUp(): void
    {
        parent::setUp();
        $importQueries = new ProductsBulkImportQueries;
        $productQueries = new ProductQueries;
        $productService = new ProductService($productQueries);
        $this->service = new ProductImportService($importQueries, $productService);
        Storage::fake('uploads');
    }

    public function test_import_creates_new_products(): void
    {
        // Create test CSV content
        $csvContent = "sku,name,description,price,stock_quantity,category,is_active\n";
        $csvContent .= "TEST001,Test Product 1,A test product,19.99,100,Electronics,true\n";
        $csvContent .= "TEST002,Test Product 2,Another test product,29.99,50,Books,false\n";

        // Create UploadedFile for testing
        $csvFile = UploadedFile::fake()->createWithContent('test_products.csv', $csvContent);

        // Create BulkImportData
        $data = BulkImportData::from([
            'csv_file' => $csvFile,
            'update_existing' => true,
            'skip_invalid' => true,
        ]);

        // Run import
        $result = $this->service->importFromUpload($data);

        // Assert results
        $this->assertEquals(2, $result->total_rows);
        $this->assertEquals(2, $result->imported_rows);
        $this->assertEquals(0, $result->updated_rows);
        $this->assertEquals(0, $result->invalid_rows);
        $this->assertEquals('completed', $result->status);

        // Assert products were created
        $this->assertDatabaseHas('products', [
            'sku' => 'TEST001',
            'name' => 'Test Product 1',
        ]);
        $this->assertDatabaseHas('products', [
            'sku' => 'TEST002',
            'name' => 'Test Product 2',
        ]);
    }

    public function test_import_updates_existing_products(): void
    {
        // Create existing product
        Product::query()->create([
            'sku' => 'EXISTING001',
            'name' => 'Original Name',
            'price' => 10.00,
            'stock_quantity' => 25,
        ]);

        // Create CSV with updated data
        $csvContent = "sku,name,description,price,stock_quantity,category,is_active\n";
        $csvContent .= "EXISTING001,Updated Name,Updated description,15.99,75,Updated Category,true\n";

        $csvFile = UploadedFile::fake()->createWithContent('test_update.csv', $csvContent);

        $data = BulkImportData::from([
            'csv_file' => $csvFile,
            'update_existing' => true,
            'skip_invalid' => true,
        ]);

        $result = $this->service->importFromUpload($data);

        // Assert results
        $this->assertEquals(1, $result->total_rows);
        $this->assertEquals(0, $result->imported_rows);
        $this->assertEquals(1, $result->updated_rows);

        // Assert product was updated
        $product = Product::query()->where('sku', 'EXISTING001')->first();
        $this->assertEquals('Updated Name', $product->name);
        $this->assertEquals(15.99, $product->price);
        $this->assertEquals(75, $product->stock_quantity);
    }

    public function test_import_handles_invalid_rows(): void
    {
        $csvContent = "sku,name,description,price,stock_quantity,category,is_active\n";
        $csvContent .= "VALID001,Valid Product,Description,19.99,100,Electronics,true\n";
        $csvContent .= ",Missing SKU,Description,29.99,50,Books,false\n"; // Invalid: missing SKU
        $csvContent .= "INVALID002,,Description,invalid_price,50,Books,false\n"; // Invalid: missing name, invalid price

        $csvFile = UploadedFile::fake()->createWithContent('test_invalid.csv', $csvContent);

        $data = BulkImportData::from([
            'csv_file' => $csvFile,
            'update_existing' => true,
            'skip_invalid' => true,
        ]);

        $result = $this->service->importFromUpload($data);

        // Assert results
        $this->assertEquals(3, $result->total_rows);
        $this->assertEquals(1, $result->imported_rows);
        $this->assertEquals(0, $result->updated_rows);
        $this->assertEquals(2, $result->invalid_rows);

        // Assert only valid product was created
        $this->assertDatabaseHas('products', [
            'sku' => 'VALID001',
        ]);
        $this->assertDatabaseMissing('products', [
            'sku' => '',
        ]);
        $this->assertDatabaseMissing('products', [
            'sku' => 'INVALID002',
        ]);

        // Assert errors were recorded
        $this->assertNotEmpty($result->errors);
        $this->assertCount(2, $result->errors);
    }

    public function test_import_handles_duplicate_skus_in_batch(): void
    {
        $csvContent = "sku,name,description,price,stock_quantity,category,is_active\n";
        $csvContent .= "DUPLICATE001,First Product,Description,19.99,100,Electronics,true\n";
        $csvContent .= "DUPLICATE001,Second Product,Description,29.99,50,Books,false\n"; // Duplicate SKU
        $csvContent .= "UNIQUE001,Unique Product,Description,39.99,75,Home,true\n";

        $csvFile = UploadedFile::fake()->createWithContent('test_duplicates.csv', $csvContent);

        $data = BulkImportData::from([
            'csv_file' => $csvFile,
            'update_existing' => true,
            'skip_invalid' => true,
        ]);

        $result = $this->service->importFromUpload($data);

        // Assert results - first occurrence should be imported, duplicate should be marked
        $this->assertEquals(3, $result->total_rows);
        $this->assertEquals(2, $result->imported_rows); // First DUPLICATE001 + UNIQUE001
        $this->assertEquals(0, $result->updated_rows);
        $this->assertEquals(1, $result->duplicate_rows); // Second DUPLICATE001

        // Assert only unique products were created
        $this->assertDatabaseHas('products', [
            'sku' => 'DUPLICATE001',
            'name' => 'First Product',
        ]);
        $this->assertDatabaseHas('products', [
            'sku' => 'UNIQUE001',
        ]);

        // Assert only one record exists for the duplicate SKU
        $this->assertEquals(1, Product::query()->where('sku', 'DUPLICATE001')->count());
    }

    public function test_upsert_logic_creates_and_updates_correctly(): void
    {
        // First import - creates new products
        $csvContent1 = "sku,name,description,price,stock_quantity,category,is_active\n";
        $csvContent1 .= "UPSERT001,Original Name,Original description,19.99,100,Electronics,true\n";
        $csvContent1 .= "UPSERT002,Another Product,Another description,29.99,50,Books,false\n";

        $csvFile1 = UploadedFile::fake()->createWithContent('test_create.csv', $csvContent1);
        $data1 = BulkImportData::from([
            'csv_file' => $csvFile1,
            'update_existing' => true,
            'skip_invalid' => true,
        ]);

        $result1 = $this->service->importFromUpload($data1);

        $this->assertEquals(2, $result1->imported_rows);
        $this->assertEquals(0, $result1->updated_rows);

        // Second import - updates existing and creates new
        $csvContent2 = "sku,name,description,price,stock_quantity,category,is_active\n";
        $csvContent2 .= "UPSERT001,Updated Name,Updated description,25.99,150,Updated Category,true\n"; // Update existing
        $csvContent2 .= "UPSERT003,New Product,New description,35.99,75,New Category,true\n"; // Create new

        $csvFile2 = UploadedFile::fake()->createWithContent('test_update.csv', $csvContent2);
        $data2 = BulkImportData::from([
            'csv_file' => $csvFile2,
            'update_existing' => true,
            'skip_invalid' => true,
        ]);

        $result2 = $this->service->importFromUpload($data2);

        $this->assertEquals(1, $result2->imported_rows); // UPSERT003
        $this->assertEquals(1, $result2->updated_rows);  // UPSERT001

        // Verify the updates
        $updatedProduct = Product::query()->where('sku', 'UPSERT001')->first();
        $this->assertEquals('Updated Name', $updatedProduct->name);
        $this->assertEquals(25.99, $updatedProduct->price);
        $this->assertEquals(150, $updatedProduct->stock_quantity);

        // Verify new product was created
        $this->assertDatabaseHas('products', [
            'sku' => 'UPSERT003',
            'name' => 'New Product',
        ]);
    }
}
