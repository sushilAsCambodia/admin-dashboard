<?php

namespace App\Services;

use App\Models\Market;
use App\Models\OddSetting;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;

class MarketService
{
    public function paginate($request): JsonResponse
    {
        try {
            $perPage = $request->rowsPerPage ?: 15;
            $page = $request->page ?: 1;
            $sortBy = $request->sortBy ?: 'name';
            $sortOrder = $request->descending == 'true' ? 'desc' : 'asc';

            $query = (new Market())->newQuery();

            $query->withCount('merchants')->with(['oddSettings', 'merchants', 'audits.user' => function ($query) {
                $query->latest()->first();
            }]);

            $query->when($request->name, function ($query) use ($request) {
                if (strlen($request->name) >= 2) {
                    $query->where('description', 'like', "%$request->name%");
                } else {
                    $query->where('name', 'like', "%$request->name%");
                }
            });
            // $query->leftJoin('merchants', 'merchants.market_id', '=', 'markets.id');
            $results = $query->orderBy($sortBy, $sortOrder)->paginate($perPage, ['*'], 'page', $page);

            return response()->json($results, 200);
        } catch (\Exception$e) {
            return generalErrorResponse($e);
        }
    }

    public function store(array $data): JsonResponse
    {
        try {
            DB::transaction(function () use ($data) {
                $market = Market::create($data);

                foreach ($data['odd_settings'] as $oddSetting) {
                    OddSetting::create(array_merge($oddSetting, ['market_id' => $market->id]));
                }
            });

            return response()->json([
                'messages' => ['Market created successfully'],
            ], 201);
        } catch (\Exception$e) {
            return generalErrorResponse($e);
        }
    }

    public function update($market, array $data): JsonResponse
    {
        try {
            DB::transaction(function () use ($market, $data) {
                $market->update($data);

                foreach ($data['odd_settings'] as $oddSetting) {
                    if (isset($oddSetting['id'])) {
                        $model = OddSetting::find($oddSetting['id']);
                        $model->update($oddSetting);
                    } else {
                        OddSetting::create($oddSetting);
                    }
                }
            });

            return response()->json([
                'messages' => ['Market updated successfully'],
            ], 200);
        } catch (\Exception$e) {
            return generalErrorResponse($e);
        }
    }

    public function delete($market): JsonResponse
    {
        try {
            $market->update(['status', 'Disabled']);
            $market->delete();

            return response()->json([
                'messages' => ['Market deleted successfully'],
            ], 200);
        } catch (\Exception$e) {
            return generalErrorResponse($e);
        }
    }

    public function checkHaveTicketStatus($request)
    {
        $ticket = Market::join('merchants', 'merchants.market_id', '=', 'markets.id')
        ->where('markets.id', $request->id)->get()->first();
        if (! empty($ticket)) {
            return response()->json([
                'status' => true,
                'data' => $ticket,
                'messages' => ['Ticket have unsettled'],
            ], 200);
        } else {
            return response()->json([
                'status' => false,
                'messages' => ['Ticket do not have unsettled'],
            ], 200);
        }
    }

    public function generateCode($request)
    {
        try {
            $marketCode = Market::select('name')->orderBy('id', 'desc')->limit(1)->first();

            if (empty($marketCode)) {
                $marketCode = 'A';
            } else {
                $marketCode = $marketCode->name;
                if ($marketCode == 'Z') {
                    $marketCode = 'AA';
                } else {
                    $marketCode++;
                }
            }

            return response()->json([
                'messages' => ['Market code created successfully'],
                'market_code' => $marketCode,
            ], 200);
        } catch (\Exception$e) {
            return generalErrorResponse($e);
        }
    }
}
