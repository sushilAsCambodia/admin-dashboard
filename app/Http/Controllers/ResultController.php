<?php

namespace App\Http\Controllers;

use App\Http\Requests\ResultFormRequest;
use App\Models\Result;
use App\Models\Ticket;
use App\Services\ResultService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ResultController extends Controller
{
    public function __construct(private ResultService $resultService)
    {
    }

    public function store(ResultFormRequest $request)
    {
        return $this->resultService->store($request->all());
    }

    public function update(ResultFormRequest $request, $resultId)
    {
        return $this->resultService->update($resultId, $request->all());
    }

    public function delete(Result $result)
    {
        // return $this->resultService->delete($result);
    }

    public function get(Result $result)
    {
        return response()->json(Result::with(['merchant', 'product', 'gamePlay'])->find($result['id']), 200);
    }

    public function getResultByGameId(Request $result)
    {
        return response()->json(Result::where('game_play_id', $result->id)->where('fetching_date', $result->date)->get(), 200);
    }

    public function all()
    {
        return response()->json(Result::with(['merchant', 'product', 'gamePlay'])->get(), 200);
    }

    public function paginate(Request $request)
    {
        return $this->resultService->paginate($request);
    }

    public function getLatestResult(Request $request)
    {
        if ($request->status == 'no') {
            $status = 'No';
        } else {
            $status = 'Yes';
        }
        if($request->status == 'all'){
            $status = 'all';
        }
        return $this->resultService->getLatestResult($request->gameid, $status);
    }

    public function getRecentResult()
    {
        return $this->resultService->getRecentResult();
    }

    public function confirm(Request $request, Result $result)
    {
        $data = Result::find($request->id);
        // $data = DB::table('ticket_slaves')
        // ->join('tickets', 'ticket_slaves.ticket_id', '=', 'tickets.id')
        // ->where('betting_date', '2022-10-06')
        // ->where('lottery_number', '0')
        // ->where('game_play_id',1)
        // ->where('ticket_slaves.merchant_id', 1)
        // ->where(function ($query) {
        //     $query->where('bet_amount', 'Big')
        //           ->orWhere('bet_amount', 'Both');
        // })
        // ->update(['prize_type' => 'C4']);
        // return response()->json($data, 200);
        return $this->resultService->confirm($result, $data);
    }

    public function storeByApi()
    {
        return $this->resultService->storeByApi();
    }

    public function getByDate(Request $request)
    {
        return $this->resultService->getByDate($request);
    }

    public function getSearchResult(ResultFormRequest $resultFormRequest)
    {
        return $this->resultService->getSearchResult($resultFormRequest);
    }

    public function getTwoMonthResult(Request $request)
    {
        return $this->resultService->getTwoMonthResult($request);
    }
}
