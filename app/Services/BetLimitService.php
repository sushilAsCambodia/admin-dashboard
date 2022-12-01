<?php

namespace App\Services;

use App\Models\BetLimit;
use App\Models\LimitSetting;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;

class BetLimitService
{
    public function paginate($request): JsonResponse
    {
        try {
            $perPage = $request->rowsPerPage ?: 15;
            $page = $request->page ?: 1;
            $sortBy = $request->sortBy ?: 'name';
            $sortOrder = $request->descending == 'true' ? 'desc' : 'asc';

            $query = (new BetLimit())->newQuery()->orderBy($sortBy, $sortOrder);

            $query->when($request->name, function ($query) use ($request) {
                if (strlen($request->name) >= 3) {
                    $query->where('description', 'like', "%$request->name%");
                } else {
                    $query->where('name', 'like', "%$request->name%");
                }
            });

            // $query->withCount('merchants')->with([ 'merchants' => function ($query) {
            //     $query->latest()->first();
            // }]);

            $query->when($request->currency_id, function ($query) use ($request) {
                $query->where('currency_id', $request->currency_id);
            });

            $results = $query->withCount('merchants')->with(['currency', 'limitSettings', 'merchants'])->paginate($perPage, ['*'], 'page', $page);

            return response()->json($results, 200);
        } catch (\Exception$e) {
            return generalErrorResponse($e);
        }
    }

    public function store(array $data): JsonResponse
    {
        try {
            DB::transaction(function () use ($data) {
                $betLimit = BetLimit::create($data);
                foreach ($data['limit_settings'] as $limitSetting) {
                    LimitSetting::create(array_merge($limitSetting, ['bet_limit_id' => $betLimit->id]));
                }
            });

            return response()->json([
                'messages' => ['Bet Limit created successfully'],
            ], 200);
        } catch (\Exception$e) {
            return generalErrorResponse($e);
        }
    }

    public function update($betLimit, array $data): JsonResponse
    {
        try {
            DB::transaction(function () use ($betLimit, $data) {
                $betLimit->update($data);

                foreach ($data['limit_settings'] as $limitSetting) {
                    if (isset($limitSetting['id'])) {
                        $model = LimitSetting::find($limitSetting['id']);
                        $model->update($limitSetting);
                    } else {
                        $limitSetting['bet_limit_id'] = $data['id'];
                        LimitSetting::create($limitSetting);
                    }
                }
            });

            return response()->json([
                'messages' => ['Bet Limit updated successfully'],
            ], 200);
        } catch (\Exception$e) {
            return generalErrorResponse($e);
        }
    }

    public function delete($betLimit): JsonResponse
    {
        try {
            DB::transaction(function () use ($betLimit) {
                $betLimit->limitSettings()->delete();
                $betLimit->delete();
            });

            return response()->json([
                'messages' => ['Bet Limit deleted successfully'],
            ], 200);
        } catch (\Exception$e) {
            return generalErrorResponse($e);
        }
    }

    public function generateLimitCode(): JsonResponse
    {
        try {
            $limitCode = BetLimit::withTrashed()->select('name')->orderBy('id', 'desc')->limit(1)->first();

            if (empty($limitCode)) {
                $limitCode = Config('constants.DEFAULT_LIMIT_CODE');
            } else {
                $limitCode = $limitCode->name;
                $limitCode++;
            }

            return response()->json([
                'messages' => ['Bet Limit code created successfully'],
                'limit_code' => $limitCode,
            ], 200);
        } catch (\Exception$e) {
            return generalErrorResponse($e);
        }
    }

    public function getLimitCode(): JsonResponse
    {
        try {
            $limitCodes = BetLimit::select('id', 'name')->groupBy('name')->orderBy('name', 'asc')->get();

            return response()->json([
                'messages' => ['Bet Limit code fetched successfully'],
                'data' => $limitCodes,
            ], 200);
        } catch (\Exception$e) {
            return generalErrorResponse($e);
        }
    }
}
