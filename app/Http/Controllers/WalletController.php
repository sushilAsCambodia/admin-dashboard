<?php

namespace App\Http\Controllers;

use App\Http\Requests\WalletFormRequest;
use App\Services\WalletService;

class WalletController extends Controller
{
    public function __construct(private WalletService $walletService)
    {
    }

    public function updateWallet(WalletFormRequest $walletFormRequest)
    {
        return $this->walletService->updateWallet($walletFormRequest);
    }

    public function getAmount(WalletFormRequest $walletFormRequest)
    {
        return $this->walletService->getAmount($walletFormRequest);
    }

    public function fetchBalance(WalletFormRequest $walletFormRequest)
    {
        return $this->walletService->fetchBalance($walletFormRequest);
    }

    public function getTransactionDetailsByID(WalletFormRequest $walletFormRequest)
    {
        return $this->walletService->getTransactionDetailsByID($walletFormRequest);
    }
}
