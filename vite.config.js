import { defineConfig } from 'vite';
import laravel from 'laravel-vite-plugin';
import { resolve } from 'path';

export default defineConfig({
    plugins: [
        laravel({
            input: [
                'resources/css/app.css',
                'resources/js/app.js',
                'resources/js/pages/dashboard.js',
                'resources/js/pages/import.js',
                'resources/js/pages/products.js',
                'resources/js/pages/uploads.js',
            ],
            refresh: true,
        }),
    ],
    resolve: {
        alias: {
            '@': resolve(__dirname, 'resources/js'),
            '@css': resolve(__dirname, 'resources/css'),
        }
    }
});
