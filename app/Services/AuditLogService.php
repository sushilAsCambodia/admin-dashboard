<?php

namespace App\Services;

use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use OwenIt\Auditing\Models\Audit;

class AuditLogService
{
    public function paginate($request): JsonResponse
    {
        try {
            $perPage = $request->rowsPerPage ?: 15;
            $page = $request->page ?: 1;
            $sortBy = $request->sortBy ?: 'name';
            $sortOrder = $request->descending == 'true' ? 'desc' : 'asc';

            $query = (new Audit())->newQuery()->orderBy($sortBy, $sortOrder);

            $query->when($request->dates, function ($query) use ($request) {
                if ($request->dates[0] == $request->dates[1]) {
                    $query->whereDate('created_at', Carbon::parse($request->dates[0])->format('Y-m-d'));
                } else {
                    $query->whereBetween('created_at', [
                        Carbon::parse($request->dates[0])->startOfDay(),
                        Carbon::parse($request->dates[1])->endOfDay(),
                    ]);
                }
            });

            $query->when($request->user_id, function ($query) use ($request) {
                $query->where('user_id', $request->user_id);
            });

            $query->when($request->event, function ($query) use ($request) {
                $query->where('event', $request->event);
            });

            $query->when($request->auditable_type, function ($query) use ($request) {
                $query->where('auditable_type', $request->auditable_type);
            });

            $results = $query->with(['user'])->paginate($perPage, ['*'], 'page', $page);

            return response()->json($results, 200);
        } catch (\Exception$e) {
            return generalErrorResponse($e);
        }
    }

    public function getModels(): JsonResponse
    {
        try {
            $query = (new Audit())->newQuery();
            $results = $query->distinct('auditable_type')->select('auditable_type')->get();

            return response()->json($results, 200);
        } catch (\Exception$e) {
            return generalErrorResponse($e);
        }
    }
}
