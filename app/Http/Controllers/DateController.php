<?php

namespace App\Http\Controllers;

use App\Services\DateService;
use Illuminate\Support\Facades\Request;

class DateController extends Controller
{
    public function __construct(private DateService $dateService)
    {
    }

    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function all(Request $request)
    {
        //  return response()->json($dates);

        return $this->dateService->all($request);
    }
}
