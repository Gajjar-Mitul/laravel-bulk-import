<?php

namespace App\Domains\Products\Queries;

use App\Domains\Products\Models\ProductImage;

class ProductImageQueries
{
    /**
     * @param  array<string, mixed>  $data
     */
    public function create(array $data): ProductImage
    {
        return ProductImage::query()->create($data);
    }

    public function findById(int $id): ?ProductImage
    {
        return ProductImage::query()->find($id);
    }

    public function getByProduct(int $productId)
    {
        return ProductImage::query()->where('product_id', $productId)
            ->orderBy('is_primary', 'desc')
            ->get();
    }

    public function delete(ProductImage $image): bool
    {
        return $image->delete() ?? false;
    }

    /**
     * Get images by upload ID
     */
    public function getByUpload(int $uploadId)
    {
        return ProductImage::query()->where('upload_id', $uploadId)->get();
    }
}
