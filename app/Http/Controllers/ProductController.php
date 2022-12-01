<?php

namespace App\Http\Controllers;

use App\Http\Requests\ProductFormRequest;
use App\Models\Product;
use App\Services\ProductService;
use Illuminate\Http\Request;

class ProductController extends Controller
{
    public function __construct(private ProductService $productService)
    {
    }

    public function store(ProductFormRequest $request)
    {
        return $this->productService->store($request->all());
    }

    public function update(ProductFormRequest $request, Product $result)
    {
        return $this->productService->update($result, $request->all());
    }

    public function delete(Product $result)
    {
        return $this->productService->delete($result);
    }

    public function get(Product $result)
    {
        return response()->json(Product::with(['merchant', 'product', 'gamePlay'])->find($result['id']), 200);
    }

    public function all()
    {
        return response()->json(Product::all(), 200);
    }

    public function paginate(Request $request)
    {
        return $this->productService->paginate($request);
    }

    public function getLatestResult(Request $request)
    {
        return $this->productService->getLatestResult($request);
    }
}
