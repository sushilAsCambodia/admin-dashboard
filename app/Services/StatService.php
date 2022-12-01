<?php

namespace App\Services;

use App\Models\LoginLog;
use App\Models\Member;
use App\Models\Ticket;
use App\Models\TicketSlave;
use App\Models\Transaction;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;

class StatService
{
    public function getThisMonthOnlineUsers(): JsonResponse
    {
        try {
            $membersCount = LoginLog::whereBetween('created_at', [
                Carbon::now()->startOfMonth(),
                Carbon::now()->endOfMonth(),
            ])->get()->count();

            $lastMonthMembersCount = LoginLog::whereBetween('created_at', [
                Carbon::now()->subMonthNoOverflow()->startOfMonth(),
                Carbon::now()->subMonthNoOverflow()->endOfMonth(),
            ])->get()->count();

            $cumulativePercent = (($membersCount - $lastMonthMembersCount) / ($membersCount + $lastMonthMembersCount)) * 100;

            return response()->json([
                'current' => $membersCount,
                'last_month' => $lastMonthMembersCount,
                'percentage' => $cumulativePercent,
            ], 200);
        } catch (\Exception$e) {
            return generalErrorResponse($e);
        }
    }

    public function getTodayBetAmount($request): JsonResponse
    {
        try {
            $amount = Ticket::leftJoin('merchants', 'merchants.id', '=', 'tickets.merchant_id')
                ->whereDate('tickets.created_at', today())
                ->where('merchants.currency_id', $request->currency_id)
                ->sum('total_amount');

            $thisMonthAmount = Ticket::leftJoin('merchants', 'merchants.id', '=', 'tickets.merchant_id')
                ->whereBetween('tickets.created_at', [
                    Carbon::now()->startOfMonth(),
                    Carbon::now()->endOfMonth(),
                ])
                ->where('merchants.currency_id', $request->currency_id)
                ->sum('total_amount');

            $lastMonthAmount = Ticket::leftJoin('merchants', 'merchants.id', '=', 'tickets.merchant_id')
                ->whereBetween('tickets.created_at', [
                    Carbon::now()->subMonthNoOverflow()->startOfMonth(),
                    Carbon::now()->subMonthNoOverflow()->endOfMonth(),
                ])
                ->where('merchants.currency_id', $request->currency_id)
                ->sum('total_amount');

            return response()->json([
                'amount' => $amount,
                'cumulative' => $thisMonthAmount - $lastMonthAmount,
                'this_month' => $thisMonthAmount,
                'last_month' => $lastMonthAmount,
            ], 200);
        } catch (\Exception$e) {
            return generalErrorResponse($e);
        }
    }

    public function getTodayMembersWL($request): JsonResponse
    {
        try {
            $data = (new TicketSlave())->newQuery()
                ->leftJoin('merchants', 'merchants.id', '=', 'ticket_slaves.merchant_id')
                ->where('merchants.currency_id', $request->currency_id)
                ->whereDate('ticket_slaves.created_at', today())
                ->select([
                    DB::raw('DATE(ticket_slaves.created_at) as date'),
                    DB::raw('sum(winning_amount) - sum(bet_net_amount) as amount'),
                ])->groupBy('date')->first()?->toArray();

            $thisMonthData = (new TicketSlave())->newQuery()
                ->leftJoin('merchants', 'merchants.id', '=', 'ticket_slaves.merchant_id')
                ->where('merchants.currency_id', $request->currency_id)
                ->whereBetween('ticket_slaves.created_at', [
                    Carbon::now()->startOfMonth(),
                    Carbon::now()->endOfMonth(),
                ])
                ->select([
                    DB::raw('DATE(ticket_slaves.created_at) as date'),
                    DB::raw('sum(winning_amount) - sum(bet_net_amount) as amount'),
                ])->groupBy('date')->first();

            $lastMonthData = (new TicketSlave())->newQuery()
                ->leftJoin('merchants', 'merchants.id', '=', 'ticket_slaves.merchant_id')
                ->where('merchants.currency_id', $request->currency_id)
                ->whereBetween('ticket_slaves.created_at', [
                    Carbon::now()->subMonthNoOverflow()->startOfMonth(),
                    Carbon::now()->subMonthNoOverflow()->endOfMonth(),
                ])
                ->select([
                    DB::raw('DATE(ticket_slaves.created_at) as date'),
                    DB::raw('sum(winning_amount) - sum(bet_net_amount) as amount'),
                ])->groupBy('date')->first();

            return response()->json(array_merge(
                $data ?? ['amount' => 0], [
                    'cumulative' => $thisMonthData?->amount ?? 0 - $lastMonthData?->amount ?? 0,
                    'this_month' => $thisMonthData?->amount,
                    'last_month' => $lastMonthData?->amount,
                ]
            ), 200);
        } catch (\Exception$e) {
            return generalErrorResponse($e);
        }
    }

    public function getThisMonthNewMembers(): JsonResponse
    {
        try {
            $membersCount = Member::whereBetween('created_at', [
                Carbon::now()->startOfMonth(),
                Carbon::now()->endOfMonth(),
            ])->get()->count();

            $lastMonthMembersCount = LoginLog::whereBetween('created_at', [
                Carbon::now()->subMonthNoOverflow()->startOfMonth(),
                Carbon::now()->subMonthNoOverflow()->endOfMonth(),
            ])->get()->count();

            $total = $membersCount + $lastMonthMembersCount;
            $cumulativePercent = (($membersCount - $lastMonthMembersCount) / ($total === 0 ? 1 : $total)) * 100;

            return response()->json([
                'current' => $membersCount,
                'last_month' => $lastMonthMembersCount,
                'percentage' => $cumulativePercent,
            ], 200);

            return response()->json($membersCount, 200);
        } catch (\Exception$e) {
            return generalErrorResponse($e);
        }
    }

    public function getOnlineStats($request): JsonResponse
    {
        try {
            $query = (new LoginLog)->newQuery();
            // ->where('user_type', Member::class);

            $query->when($request->dates, function ($query) use ($request) {
                if ($request->dates[0] == $request->dates[1]) {
                    $query->whereDate('created_at', Carbon::parse($request->dates[0])->format('Y-m-d'));
                } else {
                    $query->whereBetween('created_at', [
                        Carbon::parse($request->dates[0])->startOfDay(),
                        Carbon::parse($request->dates[1])->endOfDay(),
                    ]);
                }
            });

            $data = $query->select(DB::raw('DATE(created_at) as date'), DB::raw('count(*) as total'))
                ->groupBy('date')->orderBy('date', 'asc')->get();

            return response()->json($data, 200);
        } catch (\Exception$e) {
            return generalErrorResponse($e);
        }
    }

    public function getBetAmountStats($request): JsonResponse
    {
        try {
            $query = Ticket::leftJoin('merchants', 'merchants.id', '=', 'tickets.merchant_id')
                ->where('merchants.currency_id', $request->currency_id);

            $query->when($request->dates, function ($query) use ($request) {
                if ($request->dates[0] == $request->dates[1]) {
                    $query->whereDate('tickets.created_at', Carbon::parse($request->dates[0])->format('Y-m-d'));
                } else {
                    $query->whereBetween('tickets.created_at', [
                        Carbon::parse($request->dates[0])->startOfDay(),
                        Carbon::parse($request->dates[1])->endOfDay(),
                    ]);
                }
            });

            $data = $query->select([
                DB::raw('DATE(tickets.created_at) as date'),
                DB::raw('SUM(total_amount) as total'
                ), ])->groupBy('date')->orderBy('date', 'asc')->get();

            return response()->json($data, 200);
        } catch (\Exception$e) {
            return generalErrorResponse($e);
        }
    }

    public function getMemberWlStats($request): JsonResponse
    {
        try {
            $query = (new TicketSlave)->newQuery()
                ->leftJoin('merchants', 'merchants.id', '=', 'ticket_slaves.merchant_id')
                ->where('merchants.currency_id', $request->currency_id);

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

            $data = $query->select([
                DB::raw('DATE(ticket_slaves.created_at) as date'),
                DB::raw('sum(ticket_slaves.winning_amount) - sum(ticket_slaves.bet_net_amount) as total'),
            ])->groupBy('date')->orderBy('date', 'asc')->get();

            return response()->json($data, 200);
        } catch (\Exception$e) {
            return generalErrorResponse($e);
        }
    }

    public function getCompanyBetStats($request): JsonResponse
    {
        try {
            $query = (new TicketSlave)->newQuery()
                ->leftJoin('merchants', 'merchants.id', '=', 'ticket_slaves.merchant_id')
                ->leftJoin('game_plays', 'game_plays.id', '=', 'ticket_slaves.game_play_id')
                ->where('merchants.currency_id', $request->currency_id);

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

            $data = $query->select([
                'game_plays.name as company',
                DB::raw('sum(ticket_slaves.bet_net_amount) as total'),
            ])->groupBy('game_play_id')->orderBy('ticket_slaves.created_at', 'asc')->get();

            return response()->json($data, 200);
        } catch (\Exception$e) {
            return generalErrorResponse($e);
        }
    }

    public function getRecentMemberOnline(): JsonResponse
    {
        try {
            $query = (new LoginLog())->newQuery()->where('user_type', Member::class);

            $query->leftJoin('members', 'members.id', '=', 'login_logs.user_id')
                ->leftJoin('merchants', 'merchants.id', '=', 'members.merchant_id')
                ->select([
                    'login_logs.*',
                    'members.customer_id',
                    'members.name as member_name',
                    'merchants.name as merchant_name',
                ]);

            // $data = $query->groupBy('user_id')->orderBy('created_at', 'desc')->take(10)->get();
            $data = $query->orderBy('created_at', 'desc')->take(10)->get();

            return response()->json($data, 200);
        } catch (\Exception$e) {
            return generalErrorResponse($e);
        }
    }

    public function getRecentTransactions(): JsonResponse
    {
        try {
            $query = (new Transaction())->newQuery()->where('transactions.status', 'Complete')->orderBy('created_at', 'desc');

            $query->leftJoin('members', 'members.id', '=', 'transactions.member_id')
                ->leftJoin('merchants', 'merchants.id', '=', 'members.merchant_id')
                ->select(['transactions.*', 'members.customer_id', 'members.name as member_name', 'merchants.name as merchant_name']);

            $data = $query->take(10)->get();

            return response()->json($data, 200);
        } catch (\Exception$e) {
            return generalErrorResponse($e);
        }
    }
}
