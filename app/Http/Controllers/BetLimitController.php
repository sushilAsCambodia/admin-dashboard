<?php

namespace App\Http\Controllers;

use App\Http\Requests\BetLimitFormRequest;
use App\Models\BetLimit;
use App\Services\BetLimitService;
use Illuminate\Http\Request;

class BetLimitController extends Controller
{
    public function __construct(private BetLimitService $betLimitService)
    {
    }

    public function store(BetLimitFormRequest $request)
    {
        return $this->betLimitService->store($request->all());
    }

    public function update(BetLimitFormRequest $request, BetLimit $betLimit)
    {
        return $this->betLimitService->update($betLimit, $request->all());
    }

    public function delete(BetLimit $betLimit)
    {
        return $this->betLimitService->delete($betLimit);
    }

    public function get(BetLimit $betLimit)
    {
        return response()->json($betLimit, 200);
    }

    public function all()
    {
        $result = BetLimit::with(['currency', 'limitSettings'])->get();

        return response()->json($result, 200);
    }

    public function paginate(Request $request)
    {
        return $this->betLimitService->paginate($request);
    }

    public function generateLimitCode()
    {
        return $this->betLimitService->generateLimitCode();
    }

    public function getLimitCode()
    {
        return $this->betLimitService->getLimitCode();
    }
}
