<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Storage;

class GenerateMockImages extends Command
{
    protected $signature = 'generate:mock-images {count=100}';

    protected $description = 'Generate mock images for testing uploads';

    protected array $colors = [
        [255, 87, 51],
        [51, 255, 87],
        [51, 87, 255],
        [243, 51, 255],
        [51, 255, 243],
        [255, 51, 51],
        [51, 255, 51],
        [51, 51, 255],
        [255, 255, 51],
        [255, 51, 255],
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

        // Create image
        $image = imagecreatetruecolor($width, $height);

        // Random background color
        $backgroundColor = $this->colors[array_rand($this->colors)];
        $bgColor = imagecolorallocate($image, $backgroundColor[0], $backgroundColor[1], $backgroundColor[2]);
        imagefill($image, 0, 0, $bgColor);

        // Add text
        $white = imagecolorallocate($image, 255, 255, 255);
        $text = 'Mock Image #'.$index;
        $fontSize = 5;
        $textWidth = imagefontwidth($fontSize) * strlen($text);
        $textHeight = imagefontheight($fontSize);
        $x = ($width - $textWidth) / 2;
        $y = ($height - $textHeight) / 2;
        imagestring($image, $fontSize, $x, $y, $text, $white);

        // Add some random rectangles
        for ($j = 0; $j < random_int(3, 8); $j++) {
            $x1 = random_int(0, $width - 100);
            $y1 = random_int(0, $height - 100);
            $x2 = $x1 + random_int(50, 200);
            $y2 = $y1 + random_int(50, 200);

            $rectColor = $this->colors[array_rand($this->colors)];
            $color = imagecolorallocate($image, $rectColor[0], $rectColor[1], $rectColor[2]);
            imagerectangle($image, $x1, $y1, $x2, $y2, $color);
        }

        // Save as JPEG
        $filename = 'mock_image_'.str_pad($index, 4, '0', STR_PAD_LEFT).'.jpg';
        storage_path('app/mock-data/images/'.$filename);

        ob_start();
        imagejpeg($image, null, 85);
        $imageData = ob_get_contents();
        ob_end_clean();
        Storage::put('mock-data/images/'.$filename, $imageData);
        imagedestroy($image);
    }
}
