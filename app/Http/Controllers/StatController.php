<?php

namespace App\Http\Controllers;

use App\Services\StatService;
use Illuminate\Http\Request;

class StatController extends Controller
{
    public function __construct(protected StatService $statsService)
    {
    }

    public function getThisMonthOnlineUsers(Request $request)
    {
        return $this->statsService->getThisMonthOnlineUsers();
    }

    public function getTodayBetAmount(Request $request)
    {
        return $this->statsService->getTodayBetAmount($request);
    }

    public function getTodayMembersWL(Request $request)
    {
        return $this->statsService->getTodayMembersWL($request);
    }

    public function getThisMonthNewMembers(Request $request)
    {
        return $this->statsService->getThisMonthNewMembers();
    }

    public function getOnlineStats(Request $request)
    {
        return $this->statsService->getOnlineStats($request);
    }

    public function getBetAmountStats(Request $request)
    {
        return $this->statsService->getBetAmountStats($request);
    }

    public function getMemberWlStats(Request $request)
    {
        return $this->statsService->getMemberWlStats($request);
    }

    public function getCompanyBetStats(Request $request)
    {
        return $this->statsService->getCompanyBetStats($request);
    }

    public function getRecentMemberOnline(Request $request)
    {
        return $this->statsService->getRecentMemberOnline();
    }

    public function getRecentTransactions(Request $request)
    {
        return $this->statsService->getRecentTransactions();
    }
}
