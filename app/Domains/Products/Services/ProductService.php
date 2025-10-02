<?php

namespace App\Domains\Products\Services;

use App\Domains\Products\DataObjects\ProductData;
use App\Domains\Products\Models\Product;
use App\Domains\Products\ProductQueries;
use Exception;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class ProductService
{
    public function __construct(
        private readonly ProductQueries $queries
    ) {}

    /**
     * @param  array<string, mixed>  $filters
     */
    public function getPaginated(array $filters = [], int $perPage = 15): LengthAwarePaginator
    {
        return $this->queries->getPaginated($filters, $perPage);
    }

    public function findById(int $id): ?Product
    {
        return $this->queries->findById($id);
    }

    public function findBySku(string $sku): ?Product
    {
        return $this->queries->findBySku($sku);
    }

    public function create(ProductData $data): Product
    {
        DB::beginTransaction();
        try {
            $array = $data->toArray();
            $product = $this->queries->create($array);
            DB::commit();

            return $product;
        } catch (ValidationException $e) {
            DB::rollBack();
            throw new Exception('Validation errors: '.implode(', ', $e->validator->errors()->all()), $e->getCode(), $e);
        } catch (Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }

    public function update(Product $product, ProductData $data): Product
    {
        DB::beginTransaction();
        try {
            $array = $data->toArray();
            $updatedProduct = $this->queries->update($product, $array);
            DB::commit();

            return $updatedProduct;
        } catch (ValidationException $e) {
            DB::rollBack();
            throw new Exception('Validation errors: '.implode(', ', $e->validator->errors()->all()), $e->getCode(), $e);
        } catch (Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }

    public function delete(Product $product): bool
    {
        DB::beginTransaction();
        try {
            $result = $product->delete();
            DB::commit();

            return $result ?? false;
        } catch (Exception $exception) {
            DB::rollBack();
            throw $exception;
        }
    }

    public function skuExists(string $sku, ?int $excludeId = null): bool
    {
        return $this->queries->skuExists($sku, $excludeId);
    }

    /**
     * @return array<string>
     */
    public function getDistinctCategories(): array
    {
        return Product::query()->distinct()->pluck('category')->filter()->values()->toArray();
    }

    /**
     * @return array<string, int>
     */
    public function getStatistics(): array
    {
        return $this->queries->getStatistics();
    }
}
