<?php

// header('Access-Control-Allow-Origin:  *');

use App\Http\Controllers\AnnouncementController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\CompanyController;
use App\Http\Controllers\DateController;
use App\Http\Controllers\GamePlayController;
use App\Http\Controllers\MerchantController;
use App\Http\Controllers\ResultController;
use App\Http\Controllers\SpecialDrawController;
use App\Http\Controllers\TicketController;
use App\Http\Controllers\WalletController;
use Illuminate\Support\Facades\Route;

Route::get('/test', function () {
    echo 'frontend api';
});

Route::post('member-login', [AuthController::class, 'loginMember']);
Route::get('member-logout', [AuthController::class, 'logoutMember']);
Route::post('check-company-user', [CompanyController::class, 'checkValidUser']);
Route::post('check-company-user-data', [CompanyController::class, 'checkValidData']);

Route::post('userLottoLogin', [CompanyController::class, 'checkValidUserApi']);

Route::post('fetchToken', [MerchantController::class, 'fetchToken']);
Route::group(['middleware' => ['auth:sanctum']], function () {
    /*  Wallet  */
    Route::match(['get', 'post'], 'wallet/get-amount', [WalletController::class, 'getAmount']);
    Route::match(['get', 'post'], 'wallet/fetchWalletBalance', [WalletController::class, 'fetchBalance']);
    Route::post('wallet/updateWallet', [WalletController::class, 'updateWallet']);
    Route::match(['get', 'post'], 'getTransactionDetails', [WalletController::class, 'getTransactionDetailsByID']);

    Route::controller(TicketController::class)->group(function () {
        Route::match(['get', 'post'], 'fetchBettingReport', 'fetchBettingReport');
        Route::match(['get', 'post'], 'fetchBettingReportByCustomerId', 'fetchBettingReportByCustomerId');
        Route::match(['get', 'post'], 'fetchBettingReportByRefNumber', 'fetchBettingReportByRefNumber');
        Route::match(['get', 'post'], 'fetchBettingReportByDate', 'fetchBettingReportByDate');
        Route::match(['get', 'post'], 'fetchWinningReport', 'fetchWinningReport');
    });

    Route::get('result/recent', [ResultController::class, 'getRecentResult']);
    Route::get('results/get-by-date', [ResultController::class, 'getByDate'])->name('Result: View Result');

    // Gameplay Routes
    Route::controller(GamePlayController::class)->group(function () {
        Route::get('game-plays/paginate/{params?}', 'paginate')->name('Game Play: View Game Play');
        Route::get('game-plays/all', 'all')->name('Game Play: View Game Play');
    });

    Route::controller(AnnouncementController::class)->group(function () {
        Route::get('announcements/latest', 'latest');
    });

    Route::controller(SpecialDrawController::class)->group(function () {
        Route::get('special-draw/latest', 'latest');
    });

    Route::controller(ResultController::class)->group(function () {
        Route::get('getBetTips', 'getSearchResult');
    });

    // Ticket Routes
    Route::controller(TicketController::class)->group(function () {
        Route::post('tickets', 'store');
        Route::get('tickets/{ticket_id}', 'getTicket');
    });

    /*  Bet Details (Tickets)  */
    Route::match(['get', 'post'], 'betList', [TicketController::class, 'betList']);
    Route::match(['get', 'post'], 'betListById', [TicketController::class, 'betListById']);
    Route::match(['get', 'post'], 'betListReject', [TicketController::class, 'betListDetails']);
    Route::match(['get', 'post'], 'betListWinning', [TicketController::class, 'betListDetails']);

    /* Merchant  */
    Route::get('merchant/{merchant_id}', [MerchantController::class, 'getMerchantById']);
    /* Result  */
    Route::get('result/latest', [ResultController::class, 'getLatestResult']);

    /*  Wallet  */
    Route::match(['get', 'post'], 'wallet/get-amount', [WalletController::class, 'getAmount']);
    Route::match(['get', 'post'], 'wallet/fetchWalletBalance', [WalletController::class, 'fetchBalance']);
    Route::put('wallet/updateWallet', [WalletController::class, 'updateWallet']);

    // Currency Routes
    Route::controller(DateController::class)->group(function () {
        Route::get('dates/all', 'all');
    });

    /* Ticket  */
    Route::match(['get', 'post'], 'ticket', [TicketController::class, 'getTicket']);
    Route::match(['get', 'post'], 'getUserData', [AuthController::class, 'getUserData']);
});
