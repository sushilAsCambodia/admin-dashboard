<?php

namespace App\Services;

use App\Models\WhitelistIP;
use Illuminate\Http\JsonResponse;

class WhitelistIPService
{
    public function paginate($request): JsonResponse
    {
        try {
            $perPage = $request->rowsPerPage ?: 15;
            $page = $request->page ?: 1;
            $sortBy = $request->sortBy ?: 'created_at';
            $sortOrder = $request->descending == 'true' ? 'desc' : 'asc';

            $query = (new WhitelistIP())->newQuery()->orderBy($sortBy, $sortOrder);

            $query->leftJoin('users', 'users.id', '=', 'whitelist_ips.user_id');

            $query->when($request->address, function ($query) use ($request) {
                $query->where('address', 'like', "%$request->address%");
            });

            $query->when($request->status, function ($query) use ($request) {
                $query->where('status', $request->status == 'Enabled' ? true : false);
            });

            $results = $query->select('whitelist_ips.*')->with('user')->paginate($perPage, ['*'], 'page', $page);

            return response()->json($results, 200);
        } catch (\Exception$e) {
            return generalErrorResponse($e);
        }
    }

    public function store(array $data): JsonResponse
    {
        try {
            WhitelistIP::create($data);

            return response()->json([
                'messages' => ['IP created successfully'],
            ], 201);
        } catch (\Exception$e) {
            return generalErrorResponse($e);
        }
    }

    public function update($ip, array $data): JsonResponse
    {
        try {
            $ip->update($data);

            return response()->json([
                'messages' => ['IP updated successfully'],
            ], 200);
        } catch (\Exception$e) {
            return generalErrorResponse($e);
        }
    }

    public function delete($ip): JsonResponse
    {
        try {
            $ip->delete();

            return response()->json([
                'messages' => ['IP deleted successfully'],
            ], 200);
        } catch (\Exception$e) {
            return generalErrorResponse($e);
        }
    }
}
