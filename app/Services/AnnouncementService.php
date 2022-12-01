<?php

namespace App\Services;

use App\Models\Announcement;
use Illuminate\Http\JsonResponse;

class AnnouncementService
{
    public function paginate($request): JsonResponse
    {
        try {
            $perPage = $request->rowsPerPage ?: 15;
            $page = $request->page ?: 1;
            $sortBy = $request->sortBy ?: 'created_at';
            $sortOrder = $request->descending == 'true' ? 'desc' : 'asc';

            $query = (new Announcement())->newQuery()->orderBy($sortBy, $sortOrder);

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
            Announcement::create($data);

            return response()->json([
                'messages' => ['Announcement created successfully'],
            ], 201);
        } catch (\Exception$e) {
            return generalErrorResponse($e);
        }
    }

    public function update($announcement, array $data): JsonResponse
    {
        try {
            $announcement->update($data);

            return response()->json([
                'messages' => ['Announcement updated successfully'],
            ], 200);
        } catch (\Exception$e) {
            return generalErrorResponse($e);
        }
    }

    public function delete($announcement): JsonResponse
    {
        try {
            $announcement->delete();

            return response()->json([
                'messages' => ['Announcement deleted successfully'],
            ], 200);
        } catch (\Exception$e) {
            return generalErrorResponse($e);
        }
    }

    public function latest($request): JsonResponse
    {
        try {
            $query = (new Announcement())->newQuery()->orderBy('created_at', 'desc');
            $results = $query->limit(3)->get();

            return response()->json($results, 200);
        } catch (\Exception$e) {
            return generalErrorResponse($e);
        }
    }
}
