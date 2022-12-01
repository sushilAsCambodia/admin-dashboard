<?php

namespace App\Services;

use App\Models\Ticket;
use App\Models\TicketSlave;
use App\Models\Transaction;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class ReportService
{
    public function paginateTickets($request): JsonResponse
    {
        try {
            $perPage = $request->rowsPerPage ?: 15;
            $page = $request->page ?: 1;
            $sortBy = $request->sortBy ?: 'created_at';
            $sortOrder = $request->descending == 'true' ? 'desc' : 'asc';

            $query = (new Ticket())->newQuery()->orderBy($sortBy, $sortOrder);

            $query->when($request->list_type, function ($query) use ($request) {
                if ($request->list_type === 'bet_list') {
                    $query->where('ticket_status', 'UNSETTLED');
                }

                if ($request->list_type === 'bet_history') {
                    $query->where('ticket_status', 'SETTLED');
                }
            });

            $query->when($request->betting_dates, function ($query) use ($request) {
                if ($request->betting_dates[0] == $request->betting_dates[1]) {
                    $query->whereDate('tickets.created_at', Carbon::parse($request->betting_dates[0])->format('Y-m-d'));
                } else {
                    $query->whereBetween('tickets.created_at', [
                        Carbon::parse($request->betting_dates[0])->startOfDay(),
                        Carbon::parse($request->betting_dates[1])->endOfDay(),
                    ]);
                }
            });

            $query->when($request->draw_dates, function ($query) use ($request) {
                if ($request->draw_dates[0] == $request->draw_dates[1]) {
                    $query->whereDate('betting_date', Carbon::parse($request->draw_dates[0])->format('Y-m-d'));
                } else {
                    $query->whereBetween('betting_date', [
                        Carbon::parse($request->draw_dates[0])->startOfDay(),
                        Carbon::parse($request->draw_dates[1])->endOfDay(),
                    ]);
                }
            });

            $query->when($request->ticket_no, function ($query) use ($request) {
                $query->where('ticket_no', $request->ticket_no);
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

    public function paginateTicketSlaves($request, $id): JsonResponse
    {
        try {
            $perPage = $request->rowsPerPage ?: 15;
            $page = $request->page ?: 1;
            $sortBy = $request->sortBy ?: 'created_at';
            $sortOrder = $request->descending == 'true' ? 'desc' : 'asc';

            $query = (new TicketSlave())->newQuery()->where('ticket_id', $id)->orderBy($sortBy, $sortOrder);

            $query->when($request->dates, function ($query) use ($request) {
                if ($request->dates[0] == $request->dates[1]) {
                    $query->whereDate('ticket_slaves.created_at', Carbon::parse($request->dates[0])->format('Y-m-d'));
                } else {
                    $query->whereBetween('ticket_slaves.created_at', [
                        Carbon::parse($request->dates[0])->startOfDay(),
                        Carbon::parse($request->dates[1])->endOfDay(),
                    ]);
                }
            });

            $query->when($request->lottery_number, function ($query) use ($request) {
                $query->where('lottery_number', $request->lottery_number);
            });

            $query->when($request->game_type, function ($query) use ($request) {
                $query->where('game_type', $request->game_type);
            });

            $query->when($request->game_play_id, function ($query) use ($request) {
                $query->where('game_play_id', $request->game_play_id);
            });

            $query->leftJoin('game_plays', 'game_plays.id', '=', 'ticket_slaves.game_play_id')
                ->select([
                    'ticket_slaves.*',
                    'game_plays.name as game_play_name',
                    DB::raw('winning_amount - bet_net_amount as wl_amount'),
                ]);

            $results = $query->paginate($perPage, ['*'], 'page', $page);

            return response()->json($results, 200);
        } catch (\Exception$e) {
            return generalErrorResponse($e);
        }
    }

    public function paginateWlMerchants($request): JsonResponse
    {
        try {
            $perPage = $request->rowsPerPage ?: 15;
            $page = $request->page ?: 1;
            $sortBy = $request->sortBy ?: 'created_at';
            $sortOrder = $request->descending == 'true' ? 'desc' : 'asc';

            $query = (new Ticket())->newQuery()->orderBy($sortBy, $sortOrder);

            // Filter for user with merchant role
            if (isMechantUser()) {
                $query->whereIn('tickets.merchant_id', Auth::user()->merchants->pluck('id'));
            }

            $query->when($request->dates, function ($query) use ($request) {
                if ($request->dates[0] == $request->dates[1]) {
                    $query->whereDate('betting_date', Carbon::parse($request->dates[0])->format('Y-m-d'));
                } else {
                    $query->whereBetween('betting_date', [
                        Carbon::parse($request->dates[0])->startOfDay(),
                        Carbon::parse($request->dates[1])->endOfDay(),
                    ]);
                }
            });

            $query->when($request->merchant_id, function ($query) use ($request) {
                $query->where('merchant_id', $request->merchant_id);
            });

            $query->when($request->currency_id, function ($query) use ($request) {
                $query->where('currency_id', $request->currency_id);
            });

            $query->leftJoin('merchants', 'merchants.id', '=', 'tickets.merchant_id')
                ->leftJoin('currencies', 'currencies.id', '=', 'merchants.currency_id')
                ->leftJoin('members', 'members.id', '=', 'tickets.member_id')
                ->groupBy('tickets.merchant_id')
                ->select([
                    'merchants.id as merchant_id',
                    'merchants.name as merchant_name',
                    'members.customer_id as customer_id',
                    'merchants.code as merchant_code',
                    'currencies.code as currency_code',
                    DB::raw('count(tickets.id) as tickets_count'),
                ]);

            $results = $query->withCount([
                'ticketSlaves as total_bet_amount' => function ($query) {
                    $query->select(DB::raw('sum(bet_amount)'));
                },
                'ticketSlaves as total_net_amount' => function ($query) {
                    $query->select(DB::raw('sum(bet_net_amount)'));
                },
                'ticketSlaves as total_rebate_amount' => function ($query) {
                    $query->select(DB::raw('sum(rebate_amount)'));
                },
                'ticketSlaves as total_winning_amount' => function ($query) {
                    $query->select(DB::raw('sum(winning_amount)'));
                },
                'ticketSlaves as total_wl_amount' => function ($query) {
                    $query->select(DB::raw('sum(winning_amount) - sum(bet_net_amount)'));
                },
            ])->paginate($perPage, ['*'], 'page', $page);

            return response()->json($results, 200);
        } catch (\Exception$e) {
            return generalErrorResponse($e);
        }
    }

    public function paginateWlMerchantTickets($merchant, $request): JsonResponse
    {
        try {
            $perPage = $request->rowsPerPage ?: 15;
            $page = $request->page ?: 1;
            $sortBy = $request->sortBy ?: 'created_at';
            $sortOrder = $request->descending == 'true' ? 'desc' : 'asc';

            $query = (new Ticket())->newQuery()->orderBy($sortBy, $sortOrder);

            $query->when($request->dates, function ($query) use ($request) {
                if ($request->dates[0] == $request->dates[1]) {
                    $query->whereDate('betting_date', Carbon::parse($request->dates[0])->format('Y-m-d'));
                } else {
                    $query->whereBetween('betting_date', [
                        Carbon::parse($request->dates[0])->startOfDay(),
                        Carbon::parse($request->dates[1])->endOfDay(),
                    ]);
                }
            });

            $query->when($request->ticket_no, function ($query) use ($request) {
                $query->where('ticket_no', $request->ticket_no);
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
                ->where('tickets.merchant_id', $merchant->id)
                ->select([
                    'tickets.*',
                    'members.name as member_name',
                    'members.customer_id as customer_id',
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

    public function paginateWlMembers($request): JsonResponse
    {
        try {
            $perPage = $request->rowsPerPage ?: 15;
            $page = $request->page ?: 1;
            $sortBy = $request->sortBy ?: 'created_at';
            $sortOrder = $request->descending == 'true' ? 'desc' : 'asc';

            $query = (new Ticket())->newQuery()->orderBy($sortBy, $sortOrder);

            $query->when($request->dates, function ($query) use ($request) {
                if ($request->dates[0] == $request->dates[1]) {
                    $query->whereDate('betting_date', Carbon::parse($request->dates[0])->format('Y-m-d'));
                } else {
                    $query->whereBetween('betting_date', [
                        Carbon::parse($request->dates[0])->startOfDay(),
                        Carbon::parse($request->dates[1])->endOfDay(),
                    ]);
                }
            });

            $query->when($request->merchant_id, function ($query) use ($request) {
                $query->where('tickets.merchant_id', $request->merchant_id);
            });

            $query->when($request->currency_id, function ($query) use ($request) {
                $query->where('currency_id', $request->currency_id);
            });

            $query->leftJoin('members', 'members.id', '=', 'tickets.member_id')
                ->leftJoin('merchants', 'merchants.id', '=', 'tickets.merchant_id')
                ->leftJoin('currencies', 'currencies.id', '=', 'merchants.currency_id')
                ->groupBy('tickets.member_id')
                ->select([
                    'members.id as member_id',
                    'members.customer_id as members_id',
                    'members.name as member_name',
                    'merchants.name as merchant_name',
                    'merchants.code as merchant_code',
                    'currencies.code as currency_code',
                    DB::raw('count(tickets.id) as tickets_count'),
                ]);

            $results = $query->withCount([
                'ticketSlaves as total_bet_amount' => function ($query) {
                    $query->select(DB::raw('sum(bet_amount)'));
                },
                'ticketSlaves as total_net_amount' => function ($query) {
                    $query->select(DB::raw('sum(bet_net_amount)'));
                },
                'ticketSlaves as total_rebate_amount' => function ($query) {
                    $query->select(DB::raw('sum(rebate_amount)'));
                },
                'ticketSlaves as total_winning_amount' => function ($query) {
                    $query->select(DB::raw('sum(winning_amount)'));
                },
                'ticketSlaves as total_wl_amount' => function ($query) {
                    $query->select(DB::raw('sum(winning_amount) - sum(bet_net_amount)'));
                },
            ])->paginate($perPage, ['*'], 'page', $page);

            return response()->json($results, 200);
        } catch (\Exception$e) {
            return generalErrorResponse($e);
        }
    }

    public function paginateWlMemberTickets($member, $request): JsonResponse
    {
        try {
            $perPage = $request->rowsPerPage ?: 15;
            $page = $request->page ?: 1;
            $sortBy = $request->sortBy ?: 'created_at';
            $sortOrder = $request->descending == 'true' ? 'desc' : 'asc';

            $query = (new Ticket())->newQuery()->orderBy($sortBy, $sortOrder);

            $query->when($request->dates, function ($query) use ($request) {
                if ($request->dates[0] == $request->dates[1]) {
                    $query->whereDate('betting_date', Carbon::parse($request->dates[0])->format('Y-m-d'));
                } else {
                    $query->whereBetween('betting_date', [
                        Carbon::parse($request->dates[0])->startOfDay(),
                        Carbon::parse($request->dates[1])->endOfDay(),
                    ]);
                }
            });

            $query->when($request->ticket_no, function ($query) use ($request) {
                $query->where('ticket_no', $request->ticket_no);
            });

            $query->when($request->merchant_id, function ($query) use ($request) {
                $query->where('tickets.merchant_id', $request->merchant_id);
            });

            $query->when($request->currency_id, function ($query) use ($request) {
                $query->where('currency_id', $request->currency_id);
            });

            $query->leftJoin('members', 'members.id', '=', 'tickets.member_id')
                ->leftJoin('merchants', 'merchants.id', '=', 'tickets.merchant_id')
                ->leftJoin('markets', 'markets.id', '=', 'merchants.market_id')
                ->leftJoin('currencies', 'currencies.id', '=', 'merchants.currency_id')
                ->where('tickets.member_id', $member->id)
                ->select([
                    'tickets.*',
                    'markets.name as market_name',
                    'members.name as member_name',
                    'merchants.name as merchant_name',
                    'merchants.code as merchant_code',
                    'currencies.code as currency_code',
                ]);

            $query->withCount([
                'ticketSlaves',
                'ticketSlaves as total_bet_amount' => function ($query) {
                    $query->select(DB::raw('sum(bet_amount)'));
                },
                'ticketSlaves as total_net_amount' => function ($query) {
                    $query->select(DB::raw('sum(bet_net_amount)'));
                },
                'ticketSlaves as total_rebate_amount' => function ($query) {
                    $query->select(DB::raw('sum(rebate_amount)'));
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

    public function paginateWlMarkets($request): JsonResponse
    {
        try {
            $perPage = $request->rowsPerPage ?: 15;
            $page = $request->page ?: 1;
            $sortBy = $request->sortBy ?: 'tickets.created_at';
            $sortOrder = $request->descending == 'true' ? 'desc' : 'asc';

            $query = (new Ticket())->newQuery()->orderBy($sortBy, $sortOrder);

            $query->when($request->dates, function ($query) use ($request) {
                if ($request->dates[0] == $request->dates[1]) {
                    $query->whereDate('betting_date', Carbon::parse($request->dates[0])->format('Y-m-d'));
                } else {
                    $query->whereBetween('betting_date', [
                        Carbon::parse($request->dates[0])->startOfDay(),
                        Carbon::parse($request->dates[1])->endOfDay(),
                    ]);
                }
            });

            $query->when($request->market_id, function ($query) use ($request) {
                $query->where('markets.id', $request->market_id);
            });

            $query->when($request->currency_id, function ($query) use ($request) {
                $query->where('currency_id', $request->currency_id);
            });

            $query->leftJoin('members', 'members.id', '=', 'tickets.member_id')
                ->leftJoin('merchants', 'merchants.id', '=', 'tickets.merchant_id')
                ->leftJoin('markets', 'markets.id', '=', 'merchants.market_id')
                ->leftJoin('currencies', 'currencies.id', '=', 'merchants.currency_id')
                ->groupBy('markets.id')
                ->select([
                    'markets.id as market_id',
                    'markets.name as market_name',
                    'members.name as member_name',
                    'members.customer_id as customer_id',
                    'merchants.name as merchant_name',
                    'merchants.code as merchant_code',
                    'currencies.code as currency_code',
                    DB::raw('count(tickets.id) as tickets_count'),
                ]);

            $results = $query->withCount([
                'ticketSlaves as total_bet_amount' => function ($query) {
                    $query->select(DB::raw('sum(bet_amount)'));
                },
                'ticketSlaves as total_net_amount' => function ($query) {
                    $query->select(DB::raw('sum(bet_net_amount)'));
                },
                'ticketSlaves as total_rebate_amount' => function ($query) {
                    $query->select(DB::raw('sum(rebate_amount)'));
                },
                'ticketSlaves as total_winning_amount' => function ($query) {
                    $query->select(DB::raw('sum(winning_amount)'));
                },
                'ticketSlaves as total_wl_amount' => function ($query) {
                    $query->select(DB::raw('sum(winning_amount) - sum(bet_net_amount)'));
                },
            ])->paginate($perPage, ['*'], 'page', $page);

            return response()->json($results, 200);
        } catch (\Exception$e) {
            return generalErrorResponse($e);
        }
    }

    public function paginateWlMarketTickets($market, $request): JsonResponse
    {
        try {
            $perPage = $request->rowsPerPage ?: 15;
            $page = $request->page ?: 1;
            $sortBy = $request->sortBy ?: 'created_at';
            $sortOrder = $request->descending == 'true' ? 'desc' : 'asc';

            $query = (new Ticket())->newQuery()->orderBy($sortBy, $sortOrder);
            $query->when($request->dates, function ($query) use ($request) {
                if ($request->dates[0] == $request->dates[1]) {
                    $query->whereDate('betting_date', Carbon::parse($request->dates[0])->format('Y-m-d'));
                } else {
                    $query->whereBetween('betting_date', [
                        Carbon::parse($request->dates[0])->startOfDay(),
                        Carbon::parse($request->dates[1])->endOfDay(),
                    ]);
                }
            });

            $query->when($request->ticket_no, function ($query) use ($request) {
                $query->where('ticket_no', $request->ticket_no);
            });

            $query->when($request->merchant_id, function ($query) use ($request) {
                $query->where('merchant_id', $request->merchant_id);
            });

            $query->when($request->currency_id, function ($query) use ($request) {
                $query->where('currency_id', $request->currency_id);
            });

            $query->leftJoin('members', 'members.id', '=', 'tickets.member_id')
                ->leftJoin('merchants', 'merchants.id', '=', 'tickets.merchant_id')
                ->leftJoin('markets', 'markets.id', '=', 'merchants.market_id')
                ->leftJoin('currencies', 'currencies.id', '=', 'merchants.currency_id')
                ->where('markets.id', $market->id)
                ->select([
                    'tickets.*',
                    'members.name as member_name',
                    'members.customer_id as customer_id',
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

    public function paginateTransactions($request): JsonResponse
    {
        try {
            $perPage = $request->rowsPerPage ?: 15;
            $page = $request->page ?: 1;
            $sortBy = $request->sortBy ?: 'transactions.created_at';
            if ($request->sortBy == 'merchant_id') {
                $sortBy = 'transactions.'.$request->sortBy;
            }
            $sortOrder = $request->descending == 'true' ? 'desc' : 'asc';

            $query = (new Transaction())->newQuery()->orderBy($sortBy, $sortOrder);

            $query->when($request->transaction_id, function ($query) use ($request) {
                $query->where('transactions.transaction_id', $request->transaction_id);
            });

            $query->when($request->dates, function ($query) use ($request) {
                if ($request->dates[0] == $request->dates[1]) {
                    $query->whereDate('transactions.created_at', Carbon::parse($request->dates[0])->format('Y-m-d'));
                } else {
                    $query->whereBetween('transactions.created_at', [
                        Carbon::parse($request->dates[0])->startOfDay(),
                        Carbon::parse($request->dates[1])->endOfDay(),
                    ]);
                }
            });

            $query->when($request->transaction_type, function ($query) use ($request) {
                $query->where('transactions.transaction_type', '=', $request->transaction_type);
            });

            $query->when($request->transaction_from, function ($query) use ($request) {
                $query->where('transactions.transaction_from', $request->transaction_from);
            });

            $query->when($request->member_id, function ($query) use ($request) {
                //$query->where('transactions.member_id', $request->member_id);
                $query->where('members.customer_id', $request->member_id);
            });
            $query->when($request->merchant_id, function ($query) use ($request) {
                $query->where('transactions.merchant_id', $request->merchant_id);
            });
            $query->when($request->currency_code, function ($query) use ($request) {
                $query->where('currency', $request->currency_code);
            });

            // $query->leftJoin('merchants', 'merchants.id', '=', 'transactions.merchant_id');
            $query->leftJoin('members', 'members.id', '=', 'transactions.member_id');
            $query->leftJoin('merchants', 'merchants.id', '=', 'transactions.merchant_id');
            $query->where('amount', '>', 0);

            $query->select([
                'transactions.*',
                'merchants.name as merchant_name',
                'merchants.code as merchant_code',
                'members.name as member_name',
                'members.customer_id as member_id',
            ]);
            $results = $query->paginate($perPage, ['*'], 'page', $page);

            return response()->json($results, 200);
        } catch (\Exception$e) {
            return generalErrorResponse($e);
        }
    }

    public function paginateTransactionsGroupBy($request): JsonResponse
    {
        try {
            $perPage = $request->rowsPerPage ?: 15;
            $page = $request->page ?: 1;
            $sortBy = $request->sortBy ?: 'transactions.created_at';
            $sortOrder = $request->descending == 'true' ? 'desc' : 'asc';
            $query = (new Transaction())->newQuery()->orderBy($sortBy, $sortOrder);
            $query->when($request->transaction_id, function ($query) use ($request) {
                $query->where('transactions.transaction_id', $request->transaction_id);
            });
            $query->when($request->merchant_name, function ($query) use ($request) {
                $query->where('merchants.name', 'like', "%$request->merchant_name%");
            });
            $query->when($request->member_name, function ($query) use ($request) {
                $query->where('members.customer_id', 'like', "%$request->member_name%");
            });
            $query->when($request->dates, function ($query) use ($request) {
                if ($request->dates[0] == $request->dates[1]) {
                    $query->whereDate('transactions.created_at', Carbon::parse($request->dates[0])->format('Y-m-d'));
                } else {
                    $query->whereBetween('transactions.created_at', [
                        Carbon::parse($request->dates[0])->startOfDay(),
                        Carbon::parse($request->dates[1])->endOfDay(),
                    ]);
                }
            });
            $query->when($request->currency_code, function ($query) use ($request) {
                $query->where('transactions.currency', $request->currency_code);
            });
            if ($request->merchant_id && ! $request->member_id) {
                $query->groupBy('member_id');
                $query->leftJoin('members', 'members.id', '=', 'transactions.member_id');
                $query->select(DB::raw('count(*) as transaction_count'), 'transactions.*',
                    DB::raw("SUM(case when transaction_type='Credit' then amount else 0 end) as wallet_out"),
                    DB::raw("SUM(case when transaction_type='Debit' then amount else 0 end) as wallet_in"),
                    DB::raw("SUM(case when transaction_type='Debit' then amount else 0 end) - SUM(case when transaction_type='Credit' then amount else 0 end)  as net_amount"),
                    'members.name', 'members.customer_id');
                $query->where('transactions.merchant_id', $request->merchant_id);
            } elseif ($request->merchant_id && $request->member_id && ! $request->transaction_date) {
                $query->select(
                    DB::raw('DATE(created_at) as date'),
                    DB::raw('count(*) as transaction_count'), 'transactions.*',
                    DB::raw("SUM(case when transaction_type='Credit' then amount else 0 end) as wallet_out"),
                    DB::raw("SUM(case when transaction_type='Debit' then amount else 0 end) as wallet_in"),
                    DB::raw("SUM(case when transaction_type='Debit' then amount else 0 end) - SUM(case when transaction_type='Credit' then amount else 0 end)  as net_amount"));
                $query->groupBy('date');
                $query->where('merchant_id', $request->merchant_id);
                $query->where('member_id', $request->member_id);
            } elseif ($request->merchant_id && $request->member_id && $request->transaction_date) {
                // $query->select(DB::raw('count(*) as transaction_count'), 'transactions.*');
                $query->where('merchant_id', $request->merchant_id);
                $query->where('member_id', $request->member_id);
                $query->whereDate('created_at', $request->transaction_date);
            } else {
                $query->groupBy('merchant_id');
                $query->leftJoin('merchants', 'merchants.id', '=', 'transactions.merchant_id');
                $query->select(DB::raw('count(*) as transaction_count'), 'transactions.*',
                    DB::raw("SUM(case when transaction_type='Credit' then amount else 0 end) as wallet_out"),
                    DB::raw("SUM(case when transaction_type='Debit' then amount else 0 end) as wallet_in"),
                    DB::raw("SUM(case when transaction_type='Debit' then amount else 0 end) - SUM(case when transaction_type='Credit' then amount else 0 end)  as net_amount"),
                    'merchants.code', 'merchants.name');
            }
            $results = $query->paginate($perPage, ['*'], 'page', $page);

            return response()->json($results, 200);
        } catch (\Exception$e) {
            return generalErrorResponse($e);
        }
    }
}
