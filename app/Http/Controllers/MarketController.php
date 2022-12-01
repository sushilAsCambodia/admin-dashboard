<?php

namespace App\Http\Controllers;

use App\Http\Requests\MarketFormRequest;
use App\Models\Market;
use App\Services\MarketService;
use Illuminate\Http\Request;

class MarketController extends Controller
{
    public function __construct(private MarketService $marketService)
    {
    }

    public function store(MarketFormRequest $request)
    {
        return $this->marketService->store($request->all());
    }

    public function update(MarketFormRequest $request, Market $market)
    {
        return $this->marketService->update($market, $request->all());
    }

    public function delete(Market $market)
    {
        return $this->marketService->delete($market);
    }

    public function get(Market $market)
    {
        return response()->json($market, 200);
    }

    public function all()
    {
        return response()->json(Market::all(), 200);
    }

    public function paginate(Request $request)
    {
        return $this->marketService->paginate($request);
    }

    public function isDeleteaAble(Request $request)
    {
        $checkDeleteAble = Market::with(['merchants'])->where('markets.id', $request->id)->get()->first();

        return response()->json($checkDeleteAble, 200);
    }

    public function hasMerchant()
    {
        return response()->json(Market::whereHas('merchants')->get(), 200);
    }

    public function generateCode(Request $request)
    {
        return $this->marketService->generateCode($request);
    }
}
