<?php

namespace App\Services;

use App\Models\LoginLog;
use App\Models\Member;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;

class LoginLogService
{
    public function paginateAdmins($request): JsonResponse
    {
        try {
            $perPage = $request->rowsPerPage ?: 15;
            $page = $request->page ?: 1;
            $sortBy = $request->sortBy ?: '';
            $sortOrder = $request->descending == 'true' ? 'desc' : 'asc';

            $query = (new LoginLog())->newQuery()->where('user_type', User::class)->orderBy($sortBy, $sortOrder);

            $query->leftJoin('model_has_roles', 'model_has_roles.model_id', '=', 'login_logs.user_id')
                ->leftJoin('roles', 'roles.id', '=', 'model_has_roles.role_id');

            $query->when($request->dates, function ($query) use ($request) {
                if ($request->dates[0] == $request->dates[1]) {
                    $query->whereDate('login_logs.created_at', Carbon::parse($request->dates[0])->format('Y-m-d'));
                } else {
                    $query->whereBetween('login_logs.created_at', [
                        Carbon::parse($request->dates[0])->startOfDay(),
                        Carbon::parse($request->dates[1])->endOfDay(),
                    ]);
                }
            });

            $query->when($request->user_id, function ($query) use ($request) {
                $query->where('user_id', $request->user_id);
            });

            $query->when($request->ip_address, function ($query) use ($request) {
                $query->where('ip_address', 'like', "$request->ip_address%");
            });

            $query->when($request->role_id, function ($query) use ($request) {
                $query->where('model_has_roles.role_id', $request->role_id);
            });

            $query->select(['login_logs.*', 'roles.name as role_name']);

            $results = $query->with(['user'])->paginate($perPage, ['*'], 'page', $page);

            return response()->json($results, 200);
        } catch (\Exception$e) {
            return generalErrorResponse($e);
        }
    }

    public function paginateMembers($request): JsonResponse
    {
        try {
            $perPage = $request->rowsPerPage ?: 15;
            $page = $request->page ?: 1;
            $sortBy = $request->sortBy ?: 'created_at';
            $sortOrder = $request->descending == 'true' ? 'desc' : 'asc';

            $query = (new LoginLog())->newQuery()->where('user_type', Member::class)->orderBy($sortBy, $sortOrder);

            $query->leftJoin('members', 'members.id', '=', 'login_logs.user_id')
                ->leftJoin('merchants', 'merchants.id', '=', 'members.merchant_id');

            $query->when($request->dates, function ($query) use ($request) {
                if ($request->dates[0] == $request->dates[1]) {
                    $query->whereDate('login_logs.created_at', Carbon::parse($request->dates[0])->format('Y-m-d'));
                } else {
                    $query->whereBetween('login_logs.created_at', [
                        Carbon::parse($request->dates[0])->startOfDay(),
                        Carbon::parse($request->dates[1])->endOfDay(),
                    ]);
                }
            });

            $query->when($request->user_id, function ($query) use ($request) {
                $query->where('user_id', $request->user_id);
            });

            $query->when($request->ip_address, function ($query) use ($request) {
                $query->where('ip_address', 'like', "$request->ip_address%");
            });

            $query->when($request->merchant_id, function ($query) use ($request) {
                $query->where('members.merchant_id', $request->merchant_id);
            });

            $query->select(['login_logs.*', 'merchants.name as merchant_name', 'merchants.code as merchant_code']);

            $results = $query->with(['user'])->paginate($perPage, ['*'], 'page', $page);

            return response()->json($results, 200);
        } catch (\Exception$e) {
            return generalErrorResponse($e);
        }
    }

    public function getModels(): JsonResponse
    {
        try {
            $query = (new LoginLog())->newQuery();
            $results = $query->distinct('user_type')->select('user_type')->get();

            return response()->json($results, 200);
        } catch (\Exception$e) {
            return generalErrorResponse($e);
        }
    }
}
