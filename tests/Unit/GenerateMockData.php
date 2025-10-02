<?php

// app/Console/Commands/GenerateMockData.php

namespace App\Console\Commands;

use Faker\Factory as Faker;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Storage;

class GenerateMockData extends Command
{
    protected $signature = 'generate:mock-data {type} {count=10000}';

    protected $description = 'Generate mock CSV data for testing imports';

    public function handle(): int
    {
        $type = $this->argument('type');
        $count = (int) $this->argument('count');

        switch ($type) {
            case 'products':
                $this->generateProductsCsv($count);
                break;
            default:
                $this->error(sprintf('Unknown type: %s. Available types: products', $type));

                return 1;
        }

        return 0;
    }

    protected function generateProductsCsv(int $count): void
    {
        $this->info(sprintf('Generating %d mock products...', $count));

        $faker = Faker::create();
        $categories = ['Electronics', 'Books', 'Clothing', 'Home & Garden', 'Sports', 'Toys', 'Beauty', 'Automotive'];

        $csvContent = "sku,name,description,price,stock_quantity,category,is_active\n";

        $progressBar = $this->output->createProgressBar($count);
        $progressBar->start();

        for ($i = 0; $i < $count; $i++) {
            $sku = 'SKU'.str_pad($i + 1, 6, '0', STR_PAD_LEFT);
            $name = $faker->words(random_int(2, 4), true);
            $description = $faker->sentence(random_int(10, 20));
            $price = $faker->randomFloat(2, 5, 500);
            $stockQuantity = $faker->numberBetween(0, 1000);
            $category = $faker->randomElement($categories);
            $isActive = $faker->boolean(80) ? 'true' : 'false'; // 80% active

            // Escape commas and quotes in CSV
            $name = '"'.str_replace('"', '""', $name).'"';
            $description = '"'.str_replace('"', '""', $description).'"';

            $csvContent .= sprintf('%s,%s,%s,%s,%d,%s,%s%s', $sku, $name, $description, $price, $stockQuantity, $category, $isActive, PHP_EOL);

            $progressBar->advance();

            // Write in batches to manage memory
            if ($i % 1000 === 999 || $i === $count - 1) {
                if ($i === 999) {
                    Storage::put('mock-data/products.csv', $csvContent);
                } else {
                    Storage::append('mock-data/products.csv', $csvContent);
                }

                $csvContent = '';
            }
        }

        $progressBar->finish();
        $this->newLine();

        $filePath = storage_path('app/mock-data/products.csv');
        $this->info('Generated CSV file: '.$filePath);
        $this->info('File size: '.$this->formatBytes(filesize($filePath)));
    }

    protected function formatBytes(int $size, int $precision = 2): string
    {
        $units = ['B', 'KB', 'MB', 'GB'];

        for ($i = 0; $size > 1024 && $i < count($units) - 1; $i++) {
            $size /= 1024;
        }

        return round($size, $precision).' '.$units[$i];
    }
}
