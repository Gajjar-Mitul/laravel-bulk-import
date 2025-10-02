<?php

namespace App\Domains\Products\DataObjects;

use InvalidArgumentException;
use Spatie\LaravelData\Data;

class ProductData extends Data
{
    public function __construct(
        public string $sku,
        public string $name,
        public float $price,
        public ?string $description = null,
        public ?int $stock_quantity = 0,
        public ?string $category = null,
        public ?bool $is_active = true,
        public ?string $image_url = null,
    ) {}

    public static function rules(): array
    {
        return [
            'sku' => ['required', 'string', 'max:255', 'regex:/^[A-Za-z0-9\-_]+$/'],
            'name' => ['required', 'string', 'max:255', 'min:2'],
            'price' => ['required', 'numeric', 'min:0', 'max:999999.99'],
            'description' => ['nullable', 'string', 'max:5000'],
            'stock_quantity' => ['nullable', 'integer', 'min:0'],
            'category' => ['nullable', 'string', 'max:255'],
            'is_active' => ['nullable', 'boolean'],
            'image_url' => ['nullable', 'string', 'url'],
        ];
    }

    /**
     * @param  array<string, mixed>  $properties
     * @return array<string, mixed>
     */
    public static function prepareForPipeline(array $properties): array
    {
        // Clean and validate SKU
        if (isset($properties['sku'])) {
            $properties['sku'] = trim(strtoupper((string) $properties['sku']));
            // Throw exception for empty SKU early
            throw_if(empty($properties['sku']), new InvalidArgumentException('SKU is required and cannot be empty'));
        }

        // Clean name
        if (isset($properties['name'])) {
            $properties['name'] = trim((string) $properties['name']);
            // Throw exception for empty name early
            throw_if(empty($properties['name']), new InvalidArgumentException('Product name is required and cannot be empty'));
        }

        // Convert price to float, validate numeric
        if (isset($properties['price'])) {
            throw_unless(is_numeric($properties['price']), new InvalidArgumentException('Price must be a valid number'));

            $properties['price'] = (float) $properties['price'];
        }

        // Convert stock_quantity to int if present
        if (isset($properties['stock_quantity']) && ! is_null($properties['stock_quantity'])) {
            throw_unless(is_numeric($properties['stock_quantity']), new InvalidArgumentException('Stock quantity must be a valid number'));

            $properties['stock_quantity'] = (int) $properties['stock_quantity'];
        }

        // Convert is_active to boolean
        if (isset($properties['is_active'])) {
            if (is_string($properties['is_active'])) {
                $properties['is_active'] = in_array(
                    strtolower(trim($properties['is_active'])),
                    ['true', '1', 'yes', 'on', 'active']
                );
            } else {
                $properties['is_active'] = (bool) $properties['is_active'];
            }
        }

        // Clean other string fields
        foreach (['description', 'category', 'image_url'] as $field) {
            if (isset($properties[$field]) && is_string($properties[$field])) {
                $properties[$field] = trim($properties[$field]);
                // Convert empty strings to null
                if (empty($properties[$field])) {
                    $properties[$field] = null;
                }
            }
        }

        return $properties;
    }

    public function toArray(): array
    {
        return [
            'sku' => $this->sku,
            'name' => $this->name,
            'price' => $this->price,
            'description' => $this->description,
            'stock_quantity' => $this->stock_quantity ?? 0,
            'category' => $this->category,
            'is_active' => $this->is_active ?? true,
        ];
    }
}
