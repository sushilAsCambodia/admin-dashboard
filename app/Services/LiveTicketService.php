<?php

namespace App\Services;

use App\Models\Ticket;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;

class LiveTicketService
{
    public function paginate($request): JsonResponse
    {
        try {
            $perPage = $request->rowsPerPage ?: 15;
            $page = $request->page ?: 1;
            $sortBy = $request->sortBy ?: 'created_at';
            $sortOrder = $request->descending == 'true' ? 'desc' : 'asc';
            $start = explode(':', $request->times[0]);
            $end = explode(':', $request->times[1]);

            $query = (new Ticket())->newQuery()->orderBy($sortBy, $sortOrder);

            $query->where('ticket_status', 'UNSETTLED')
                ->whereBetween('tickets.created_at', [
                    Carbon::today()->setTime($start[0], $start[1], $start[2])->format('Y-m-d H:i:s'),
                    Carbon::today()->setTime($end[0], $end[1], $end[2])->format('Y-m-d H:i:s'),
                ]);

            $query->when($request->min_amount, function ($query) use ($request) {
                $query->where('total_amount', '>=', $request->min_amount);
            });

            $query->when($request->ticket_no, function ($query) use ($request) {
                $query->where('ticket_no', $request->ticket_no);
            });

            $query->when($request->bet_number, function ($query) use ($request) {
                $query->where('bet_number', $request->bet_number);
            });

            $query->when($request->merchant_id, function ($query) use ($request) {
                $query->where('tickets.merchant_id', $request->merchant_id);
            });

            $query->when($request->market_id, function ($query) use ($request) {
                $query->where('market_id', $request->market_id);
            });

            $query->when($request->currency_id, function ($query) use ($request) {
                $query->where('currency_id', $request->currency_id);
            });

            $query->leftJoin('members', 'members.id', '=', 'tickets.member_id')
                ->leftJoin('merchants', 'merchants.id', '=', 'tickets.merchant_id')
                ->leftJoin('markets', 'markets.id', '=', 'merchants.market_id')
                ->leftJoin('currencies', 'currencies.id', '=', 'merchants.currency_id')
                ->select([
                    'tickets.*',
                    'members.name as member_name',
                    'members.customer_id as member_id',
                    'markets.name as market_name',
                    'merchants.name as merchant_name',
                    'merchants.code as merchant_code',
                    'currencies.code as currency_code',
                ]);

            $query->withCount([
                'ticketSlaves',
                'ticketSlaves as total_bet_amount' => function ($query) {
                    $query->select(DB::raw('sum(bet_amount)'));
                },
                'ticketSlaves as total_rebate_amount' => function ($query) {
                    $query->select(DB::raw('sum(rebate_amount)'));
                },
                'ticketSlaves as total_net_amount' => function ($query) {
                    $query->select(DB::raw('sum(bet_net_amount)'));
                },
                'ticketSlaves as total_winning_amount' => function ($query) {
                    $query->select(DB::raw('sum(winning_amount)'));
                },
                'ticketSlaves as total_wl_amount' => function ($query) {
                    $query->select(DB::raw('sum(winning_amount) - sum(bet_net_amount)'));
                },
            ]);

            $results = $query->paginate($perPage, ['*'], 'page', $page);

            return response()->json($results, 200);
        } catch (\Exception$e) {
            return generalErrorResponse($e);
        }
    }
}
