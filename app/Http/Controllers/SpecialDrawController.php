<?php

namespace App\Http\Controllers;

use App\Http\Requests\SpecialDrawFormRequest;
use App\Models\SpecialDraw;
use App\Services\SpecialDrawService;
use Illuminate\Http\Request;

class SpecialDrawController extends Controller
{
    public function __construct(private SpecialDrawService $specialDrawService)
    {
    }

    public function store(SpecialDrawFormRequest $request)
    {
        return $this->specialDrawService->store($request->all());
    }

    public function update(SpecialDrawFormRequest $request, SpecialDraw $specialDraw)
    {
        return $this->specialDrawService->update($specialDraw, $request->all());
    }

    public function delete(SpecialDraw $specialDraw)
    {
        return $this->specialDrawService->delete($specialDraw);
    }

    public function get(SpecialDraw $specialDraw)
    {
        return response()->json($specialDraw, 200);
    }

    public function getByDate(Request $request)
    {
        $isMonth = explode('-', $request->date);
        if (count($isMonth) == 3) {
            // return count($isMonth);
            return response()->json(SpecialDraw::where('draw_date', $request->date)->get(), 200);
        } else {
            // return count($isMonth);
            $getForMonthStart = $request->date.'-01';
            $lastday = date('t', strtotime('today'));
            $getForMonthEnd = $request->date.'-'.$lastday;
            // return response()->json(SpecialDraw::where('draw_date', '>=', $getForMonthStart)->where('draw_date', '<=', $getForMonthEnd)->get(), 200);
            return response()->json(SpecialDraw::all(), 200);
        }
    }

    public function all()
    {
        return response()->json(SpecialDraw::all(), 200);
    }

    public function paginate(Request $request)
    {
        return $this->specialDrawService->paginate($request);
    }

    public function latest()
    {
        return $this->specialDrawService->latest();
    }
}
