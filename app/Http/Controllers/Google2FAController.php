<?php

namespace App\Http\Controllers;

use App\Http\Requests\LoginFormRequest;
use App\Services\Google2FAService;
use Illuminate\Http\Request;

class Google2FAController extends Controller
{
    public function __construct(private Google2FAService $google2faService)
    {
    }

    public function verifyUser(LoginFormRequest $request)
    {
        return $this->google2faService->verifyUser($request);
    }

    public function verifyCode(Request $request)
    {
        return $this->google2faService->verifyCode($request);
    }

    public function enableGa(Request $request)
    {
        return $this->google2faService->enableGa($request);
    }
}
