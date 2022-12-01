<?php

namespace App\Http\Controllers;

use App\Services\LoginLogService;
use Illuminate\Http\Request;

class LoginLogController extends Controller
{
    public function __construct(private LoginLogService $loginLogService)
    {
    }

    public function paginateAdmins(Request $request)
    {
        return $this->loginLogService->paginateAdmins($request);
    }

    public function paginateMembers(Request $request)
    {
        return $this->loginLogService->paginateMembers($request);
    }

    public function getModels()
    {
        return $this->loginLogService->getModels();
    }
}
