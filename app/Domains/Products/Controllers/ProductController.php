<?php

declare(strict_types=1);

namespace App\Domains\Products\Controllers;

use App\Domains\Products\DataObjects\ProductData;
use App\Domains\Products\Models\Product;
use App\Domains\Products\Services\ProductService;
use App\Http\Controllers\Controller;
use Illuminate\View\View;

class ProductController extends Controller
{
    public function __construct(
        private readonly ProductService $productService
    ) {}

    public function index(): View
    {
        return view('pages.products.index', [
            'pageTitle' => 'Products Management',
        ]);
    }

    public function create(): View
    {
        return view('pages.products.create', [
            'pageTitle' => 'Add New Product',
        ]);
    }

    public function store(ProductData $data)
    {
        // $data is already validated and cast
        $this->productService->create($data);

        return redirect()->route('products.index')
            ->with('success', 'Product created');
    }

    public function edit(Product $product): View
    {
        return view('pages.products.edit', [
            'product' => $product,
            'pageTitle' => 'Edit Product',
        ]);
    }

    public function update(int $id, ProductData $data)
    {
        $product = $this->productService->findById($id);
        abort_if(! $product instanceof Product, 404);

        $this->productService->update($product, $data);

        return redirect()
            ->route('products.index')
            ->with('success', 'Product updated successfully');
    }

    public function destroy(Product $product)
    {
        $this->productService->delete($product);

        return redirect()
            ->route('products.index')
            ->with('success', 'Product deleted successfully');
    }
}
