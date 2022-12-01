<?php

namespace App\Services;

use App\Models\Setting;
use Illuminate\Http\JsonResponse;

class SettingService
{
    public function paginate($request): JsonResponse
    {
        try {
            $perPage = $request->rowsPerPage ?: 15;
            $page = $request->page ?: 1;
            $sortBy = $request->sortBy ?: 'created_at';
            $sortOrder = $request->descending == 'true' ? 'desc' : 'asc';

            $query = (new Setting())->newQuery()->orderBy($sortBy, $sortOrder);

            $query->when($request->name, function ($query) use ($request) {
                $query->where('name', 'like', "%$request->name%");
            });

            $query->where('status', 'Active');

            $results = $query->paginate($perPage, ['*'], 'page', $page);

            return response()->json($results, 200);
        } catch (\Exception$e) {
            return generalErrorResponse($e);
        }
    }

    public function store(array $data): JsonResponse
    {
        try {
            Setting::create($data);

            return response()->json([
                'messages' => ['Setting created successfully'],
            ], 201);
        } catch (\Exception$e) {
            return generalErrorResponse($e);
        }
    }

    public function update($setting, array $data): JsonResponse
    {
        try {
            $setting->update($data);

            return response()->json([
                'messages' => ['Setting updated successfully'],
            ], 200);
        } catch (\Exception$e) {
            return generalErrorResponse($e);
        }
    }

    public function delete($setting): JsonResponse
    {
        try {
            $setting->delete();

            return response()->json([
                'messages' => ['Setting deleted successfully'],
            ], 200);
        } catch (\Exception$e) {
            return generalErrorResponse($e);
        }
    }
}
