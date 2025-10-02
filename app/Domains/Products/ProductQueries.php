<?php

namespace App\Domains\Products;

use App\Domains\Products\Models\Product;
use Illuminate\Pagination\LengthAwarePaginator;

class ProductQueries
{
    public function findById(int $id): ?Product
    {
        return Product::query()->find($id);
    }

    /**
     * @param  array<string, mixed>  $filters
     */
    public function getPaginated(array $filters = [], int $perPage = 15): LengthAwarePaginator
    {
        $query = Product::with(['images', 'primaryImage']);

        if (! empty($filters['search'])) {
            $search = $filters['search'];
            $query->where(function ($q) use ($search): void {
                $q->where('name', 'like', sprintf('%%%s%%', $search))
                    ->orWhere('sku', 'like', sprintf('%%%s%%', $search))
                    ->orWhere('description', 'like', sprintf('%%%s%%', $search));
            });
        }

        if (! empty($filters['category'])) {
            $query->where('category', $filters['category']);
        }

        if (isset($filters['is_active']) && $filters['is_active'] !== '') {
            $query->where('is_active', (bool) $filters['is_active']);
        }

        return $query->orderBy('created_at', 'desc')->paginate($perPage);
    }

    public function findBySku(string $sku): ?Product
    {
        return Product::query()->where('sku', $sku)->first();
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function create(array $data): Product
    {
        return Product::query()->create($data);
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function update(Product $product, array $data): Product
    {
        $product->update($data);

        return $product;
    }

    public function skuExists(string $sku, ?int $excludeId = null): bool
    {
        $query = Product::query()->where('sku', $sku);
        if ($excludeId !== null && $excludeId !== 0) {
            $query->where('id', '!=', $excludeId);
        }

        return $query->exists();
    }

    /**
     * @return array<string, int>
     */
    public function getStatistics(): array
    {
        return [
            'total_products' => Product::query()->count(),
            'active_products' => Product::query()->where('is_active', true)->count(),
            'inactive_products' => Product::query()->where('is_active', false)->count(),
        ];
    }
}
