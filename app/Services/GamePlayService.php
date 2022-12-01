<?php

namespace App\Services;

use App\Models\GamePlay;
use Illuminate\Http\JsonResponse;

class GamePlayService
{
    public function paginate($request): JsonResponse
    {
        try {
            if ($request->sortBy == 'sl') {
                $request->sortBy = 'id';
            }

            $perPage = $request->rowsPerPage ?: 15;
            $page = $request->page ?: 1;
            $sortBy = $request->sortBy ?: 'created_at';
            $sortOrder = $request->descending == 'true' ? 'desc' : 'asc';

            $query = (new GamePlay())->newQuery()->orderBy($sortBy, $sortOrder);

            $query->when($request->name, function ($query) use ($request) {
                $query->where('name', 'like', "%$request->name%");
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
            GamePlay::create($data);

            return response()->json([
                'messages' => ['Game Play created successfully'],
            ], 201);
        } catch (\Exception$e) {
            return generalErrorResponse($e);
        }
    }

    public function update($gamePlay, array $data): JsonResponse
    {
        try {
            $gamePlay->update($data);

            return response()->json([
                'messages' => ['Game Play updated successfully'],
            ], 200);
        } catch (\Exception$e) {
            return generalErrorResponse($e);
        }
    }

    public function delete($gamePlay): JsonResponse
    {
        try {
            $gamePlay->delete();

            return response()->json([
                'messages' => ['Game Play deleted successfully'],
            ], 200);
        } catch (\Exception$e) {
            return generalErrorResponse($e);
        }
    }
}
