<?php

namespace App\Http\Controllers;

use App\Models\Market;
use App\Models\Member;
use App\Models\Merchant;
use App\Services\ReportService;
use Illuminate\Http\Request;

class ReportController extends Controller
{
    public function __construct(private ReportService $reportService)
    {
    }

    public function paginateTickets(Request $request)
    {
        return $this->reportService->paginateTickets($request);
    }

    public function paginateTicketSlaves(Request $request, $id)
    {
        return $this->reportService->paginateTicketSlaves($request, $id);
    }

    public function paginateWlMerchants(Request $request)
    {
        return $this->reportService->paginateWlMerchants($request);
    }

    public function paginateWlMerchantTickets(Request $request, Merchant $merchant)
    {
        return $this->reportService->paginateWlMerchantTickets($merchant, $request);
    }

    public function paginateWlMembers(Request $request)
    {
        return $this->reportService->paginateWlMembers($request);
    }

    public function paginateWlMemberTickets(Request $request, Member $member)
    {
        return $this->reportService->paginateWlMemberTickets($member, $request);
    }

    public function paginateWlMarkets(Request $request)
    {
        return $this->reportService->paginateWlMarkets($request);
    }

    public function paginateWlMarketTickets(Request $request, Market $market)
    {
        return $this->reportService->paginateWlMarketTickets($market, $request);
    }

    public function paginateTransactions(Request $request)
    {
        return $this->reportService->paginateTransactions($request);
    }

    public function paginateTransactionsGroupBy(Request $request)
    {
        return $this->reportService->paginateTransactionsGroupBy($request);
    }
}
