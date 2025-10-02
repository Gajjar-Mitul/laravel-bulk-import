<?php

namespace Database\Seeders;

use App\Domains\Products\Models\Product;
use Illuminate\Database\Seeder;

class LargeProductSeeder extends Seeder
{
    public function run(): void
    {
        echo "Creating 1,000 products (faster)...\n";

        for ($i = 1; $i <= 1000; $i++) {
            Product::create([
                'sku' => 'SKU'.str_pad($i, 6, '0', STR_PAD_LEFT),
                'name' => 'Product '.$i,
                'description' => 'Description for product '.$i,
                'price' => random_int(100, 10000) / 100,
                'stock_quantity' => random_int(0, 1000),
                'category' => 'Electronics',
                'is_active' => true,
            ]);

            if ($i % 100 === 0) {
                echo "Created {$i} products...\n";
            }
        }
    }
}
