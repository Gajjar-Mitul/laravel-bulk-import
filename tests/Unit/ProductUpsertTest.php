<?php

namespace Tests\Unit;

use App\Domains\Products\ProductQueries;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProductUpsertTest extends TestCase
{
    use RefreshDatabase;

    public function test_product_creates_new_when_sku_not_exists(): void
    {
        $queries = new ProductQueries;

        $queries->create([
            'sku' => 'TEST001',
            'name' => 'Test Product',
            'price' => 19.99,
        ]);

        $this->assertDatabaseHas('products', [
            'sku' => 'TEST001',
            'name' => 'Test Product',
        ]);
    }

    public function test_sku_exists_check_works(): void
    {
        $queries = new ProductQueries;

        $queries->create([
            'sku' => 'EXISTING',
            'name' => 'Existing',
            'price' => 10,
        ]);

        $this->assertTrue($queries->skuExists('EXISTING'));
        $this->assertFalse($queries->skuExists('NOT_EXISTING'));
    }
}
