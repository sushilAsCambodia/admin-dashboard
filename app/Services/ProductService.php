<?php

namespace App\Services;

use App\Models\Product;
use Illuminate\Http\JsonResponse;

class ProductService
{
    public function paginate($request): JsonResponse
    {
        try {
            $perPage = $request->rowsPerPage ?: 15;
            $page = $request->page ?: 1;
            $sortBy = $request->sortBy ?: 'id';
            $sortOrder = $request->descending == 'true' ? 'desc' : 'asc';

            $query = (new Product())->newQuery()->orderBy($sortBy, $sortOrder);
            $query->when($request->title, function ($query) use ($request) {
                $query->where('title', 'like', "%$request->title%");
            });

            $results = $query->paginate($perPage, ['*'], 'page', $page);

            return response()->json($results, 200);
        } catch (\Exception$e) {
            return generalErrorResponse($e);
        }
    }

    public function store(array $data): JsonResponse
    {
        try {
            Product::create($data);

            return response()->json([
                'messages' => ['Result created successfully'],
            ], 200);
        } catch (\Exception$e) {
            return generalErrorResponse($e);
        }
    }

    public function update($result, array $data): JsonResponse
    {
        try {
            return response()->json([
                'messages' => ['Result updated successfully'],
            ], 200);
        } catch (\Exception$e) {
            return generalErrorResponse($e);
        }
    }

    public function delete($result): JsonResponse
    {
        try {
            return response()->json([
                'messages' => ['Result deleted successfully'],
            ], 200);
        } catch (\Exception$e) {
            return generalErrorResponse($e);
        }
    }

    public function getLatestResult(): JsonResponse
    {
        try {
            $result = Product::orderBy('id', 'desc')->first();
            if (! empty($result)) {
                $results = Product::with(['merchant', 'product', 'gamePlay'])->whereDate('fetching_date_new', $result->fetching_date_new)->orderBy('id', 'desc')->get();

                return response()->json([
                    'data' => $results,
                    'success' => true,
                    'messages' => 'Latest result fetched successfully',
                ], 200);
            } else {
                return response()->json([
                    'success' => false,
                    'messages' => 'No Results',
                ], 200);
            }
        } catch (\Exception$e) {
            return generalErrorResponse($e);
        }
    }
}
