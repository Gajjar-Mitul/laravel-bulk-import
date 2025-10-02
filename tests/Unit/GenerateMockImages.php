<?php

// app/Console/Commands/GenerateMockImages.php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Storage;
use Intervention\Image\ImageManagerStatic as Image;

class GenerateMockImages extends Command
{
    protected $signature = 'generate:mock-images {count=100}';

    protected $description = 'Generate mock images for testing uploads';

    protected array $colors = [
        '#FF5733', '#33FF57', '#3357FF', '#F333FF', '#33FFF3',
        '#FF3333', '#33FF33', '#3333FF', '#FFFF33', '#FF33FF',
    ];

    public function handle(): void
    {
        $count = (int) $this->argument('count');

        $this->info(sprintf('Generating %d mock images...', $count));

        // Create directory if it doesn't exist
        Storage::makeDirectory('mock-data/images');

        $progressBar = $this->output->createProgressBar($count);
        $progressBar->start();

        for ($i = 0; $i < $count; $i++) {
            $this->generateMockImage($i + 1);
            $progressBar->advance();
        }

        $progressBar->finish();
        $this->newLine();

        $this->info(sprintf('Generated %d images in storage/app/mock-data/images/', $count));
    }

    protected function generateMockImage(int $index): void
    {
        // Random dimensions between 800x600 and 1920x1080
        $width = random_int(800, 1920);
        $height = random_int(600, 1080);

        // Create image with random color background
        $backgroundColor = $this->colors[array_rand($this->colors)];
        $image = Image::canvas($width, $height, $backgroundColor);
        $image->text('Mock Image #'.$index, $width / 2, $height / 2, function ($font): void {
            $font->file(in_array(public_path('fonts/arial.ttf'), ['', '0'], true) ? null : public_path('fonts/arial.ttf'));
            $font->size(48);
            $font->color('#FFFFFF');
            $font->align('center');
            $font->valign('middle');
        });

        // Add some random rectangles
        for ($j = 0; $j < random_int(3, 8); $j++) {
            $x1 = random_int(0, $width - 100);
            $y1 = random_int(0, $height - 100);
            $x2 = $x1 + random_int(50, 200);
            $y2 = $y1 + random_int(50, 200);

            $rectColor = $this->colors[array_rand($this->colors)];
            $image->rectangle($x1, $y1, $x2, $y2, function ($draw) use ($rectColor): void {
                $draw->background($rectColor);
                $draw->border(2, '#000000');
            });
        }

        // Save as JPEG
        $filename = 'mock_image_'.str_pad($index, 4, '0', STR_PAD_LEFT).'.jpg';
        $imagePath = storage_path('app/mock-data/images/'.$filename);

        $image->save($imagePath, 85); // 85% quality
    }
}
