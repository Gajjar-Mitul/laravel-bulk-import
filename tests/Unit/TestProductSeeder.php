<?php

// database/seeders/TestProductSeeder.php

namespace Database\Seeders;

use App\Models\Product;
use Faker\Factory as Faker;
use Illuminate\Database\Seeder;

class TestProductSeeder extends Seeder
{
    public function run(): void
    {
        $faker = Faker::create();
        $categories = ['Electronics', 'Books', 'Clothing', 'Home & Garden', 'Sports'];

        // Create 100 test products
        for ($i = 0; $i < 100; $i++) {
            Product::create([
                'sku' => 'TEST-'.str_pad($i + 1, 4, '0', STR_PAD_LEFT),
                'name' => $faker->words(random_int(2, 4), true),
                'description' => $faker->sentence(random_int(10, 20)),
                'price' => $faker->randomFloat(2, 5, 500),
                'stock_quantity' => $faker->numberBetween(0, 1000),
                'category' => $faker->randomElement($categories),
                'is_active' => $faker->boolean(80),
                'metadata' => [
                    'brand' => $faker->company(),
                    'weight' => $faker->randomFloat(2, 0.1, 10).' kg',
                    'color' => $faker->colorName(),
                ],
            ]);
        }
    }
}
