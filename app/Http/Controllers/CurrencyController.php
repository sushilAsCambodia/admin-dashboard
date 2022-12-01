<?php

namespace App\Http\Controllers;

use App\Http\Requests\CurrencyFormRequest;
use App\Models\Currency;
use App\Services\CurrencyService;
use Illuminate\Http\Request;

class CurrencyController extends Controller
{
    public function __construct(private CurrencyService $currencyService)
    {
    }

    public function store(CurrencyFormRequest $request)
    {
        return $this->currencyService->store($request->all());
    }

    public function update(CurrencyFormRequest $request, Currency $currency)
    {
        return $this->currencyService->update($currency, $request->all());
    }

    public function delete(Currency $currency)
    {
        return $this->currencyService->delete($currency);
    }

    public function get(Currency $currency)
    {
        return response()->json($currency, 200);
    }

    public function all()
    {
        return response()->json(Currency::all(), 200);
    }

    public function paginate(Request $request)
    {
        return $this->currencyService->paginate($request);
    }
}
