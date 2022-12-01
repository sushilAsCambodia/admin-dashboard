<?php

namespace App\Services;

use App\Models\Merchant;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Auth;

class MerchantService
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
            $sortOrder = $request->descending === 'true' ? 'desc' : 'asc';

            $query = (new Merchant())->newQuery();

            $query->when($request->name, function ($query) use ($request) {
                $query->where('merchants.name', 'like', "%$request->name%");
            });

            $query->when($request->status, function ($query) use ($request) {
                $query->where('merchants.status', $request->status);
            });

            $query->when($request->market_id, function ($query) use ($request) {
                $query->where('market_id', $request->market_id);
            });

            $query->when($request->currency_id, function ($query) use ($request) {
                $query->where('currencies.id', $request->currency_id);
            });

            $query->leftJoin('markets', 'markets.id', '=', 'merchants.market_id')
                ->leftJoin('currencies', 'currencies.id', '=', 'merchants.currency_id')
                ->leftJoin('bet_limits', 'bet_limits.id', '=', 'merchants.bet_limit_id')
                ->select('merchants.*', 'markets.name as market_name', 'currencies.code as currency_code', 'bet_limits.name as bet_limit_name')
            //->select('merchants.name as name','merchants.code','merchants.bet_limit_id','merchants.market_id','merchants.currency_id','merchants.secret_key','merchants.token','merchants.credit_limit','merchants.description','merchants.status','merchants.created_at', 'markets.name as market_name', 'currencies.code as currency_code')
                ->withCount('members');

            if ($sortBy === 'markets.name') {
                $query->orderBy('market_name', $sortOrder);
            }if ($sortBy === 'currency.code') {
                $query->orderBy('currency_code', $sortOrder);
            } else {
                $query->orderBy($sortBy, $sortOrder);
            }

            // Filter for user with merchant role
            if (isMechantUser()) {
                $query->whereIn('merchants.id', Auth::user()->merchants->pluck('id'));
            }

            $results = $query->with(['market.oddSettings.gamePlay'])->paginate($perPage, ['*'], 'page', $page);

            return response()->json($results, 200);
        } catch (\Exception$e) {
            return generalErrorResponse($e);
        }
    }

    public function store(array $data): JsonResponse
    {
        try {
            Merchant::create($data);

            return response()->json([
                'messages' => ['Merchant created successfully'],
            ], 201);
        } catch (\Exception$e) {
            return generalErrorResponse($e);
        }
    }

    public function update($merchant, array $data): JsonResponse
    {
        try {
            $data = Arr::except($data, ['secret_key']);
            $merchant->update($data);

            return response()->json([
                'messages' => ['Merchant updated successfully'],
            ], 200);
        } catch (\Exception$e) {
            return generalErrorResponse($e);
        }
    }

    public function delete($merchant): JsonResponse
    {
        try {
            $merchant->delete();

            return response()->json([
                'messages' => ['Merchant deleted successfully'],
            ], 200);
        } catch (\Exception$e) {
            return generalErrorResponse($e);
        }
    }

    public function getToken($params): JsonResponse
    {
        try {
            $results = Merchant::select('token')->where('id', $params['id'])->where('secret_key', $params['secret_key'])->get();

            return response()->json(
                count($results) > 0 ? $results[0]['token'] : '', 200);
        } catch (\Exception$e) {
            return generalErrorResponse($e);
        }
    }

    public function getMerchantById($id): JsonResponse
    {
        try {
            $member = Merchant::with(['market', 'currency', 'betLimit', 'betLimit.limitSettings', 'market.oddSettings', 'market.oddSettings.gamePlay'])->find($id);

            if (! empty($member)) {
                return response()->json(['data' => $member, 'success' => true, 'message' => 'Merchant data fetched successfully'], 200);
            } else {
                return response()->json(['success' => false, 'message' => 'Merchant not found'], 404);
            }
        } catch (\Exception$e) {
            return generalErrorResponse($e);
        }
    }

    public function fetchToken($params): JsonResponse
    {
        try {
            $results = Merchant::select('id')->where('id', $params['merchant_id'])->where('secret_key', $params['secret_key'])->first();
            if (! empty($results)) {
                return response()->json(
                    [
                        'success' => true,
                        'message' => 'Merchant token fetched successfully',
                        'token' => ! empty($results) ? $results->createToken($results->id)->plainTextToken : '',
                    ], 200);
            } else {
                return response()->json(
                    [
                        'success' => false,
                        'message' => 'Invaid Merchant',
                    ], 200);
            }
        } catch (\Exception$e) {
            return generalErrorResponse($e);
        }
    }

    public function all()
    {
        $query = (new Merchant())->newQuery()->orderBy('id', 'desc');

        // Filter for user with merchant role
        if (isMechantUser()) {
            $query->whereIn('merchants.id', Auth::user()->merchants->pluck('id'));
        }

        return response()->json($query->with(['market', 'currency', 'betLimit', 'betLimit.limitSettings', 'market.oddSettings'])->get(), 200);
    }
}
