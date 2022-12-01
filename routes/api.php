<?php

use App\Http\Controllers\AnnouncementController;
use App\Http\Controllers\AuditLogController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\BetLimitController;
use App\Http\Controllers\CompanyController;
use App\Http\Controllers\CurrencyController;
use App\Http\Controllers\CustomerController;
use App\Http\Controllers\DateController;
use App\Http\Controllers\FileUploadController;
use App\Http\Controllers\GamePlayController;
use App\Http\Controllers\Google2FAController;
use App\Http\Controllers\LanguageController;
use App\Http\Controllers\LoginLogController;
use App\Http\Controllers\MarketController;
use App\Http\Controllers\MemberController;
use App\Http\Controllers\MerchantController;
use App\Http\Controllers\ProductController;
use App\Http\Controllers\ReportController;
use App\Http\Controllers\ResultController;
use App\Http\Controllers\RoleController;
use App\Http\Controllers\SettingController;
use App\Http\Controllers\SpecialDrawController;
use App\Http\Controllers\StatController;
use App\Http\Controllers\TicketController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\WhitelistIPController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| is assigned the "api" middleware group. Enjoy building your API!
|
 */

Route::group(['middleware' => 'whitelist_ip'], function () {
    // Sanctum endpoint to generate cookie
    Route::get('sanctum/csrf-cookie', function () {
        return response('OK', 204);
    });

    Route::controller(AuthController::class)->group(function () {
        Route::post('auth/login', 'login');
    });

    //  2FA  and generate QR code
    Route::controller(Google2FAController::class)->group(function () {
        Route::post('auth/2fa/verify-user', 'verifyUser');
        Route::post('auth/2fa/enable-ga', 'enableGa');
        Route::post('auth/2fa/verify-code', 'verifyCode');
    });
});

// Language Routes
Route::controller(LanguageController::class)->group(function () {
    Route::get('languages/paginate/{params?}', 'paginate');
    Route::get('languages/all', 'all');
});

Route::controller(AnnouncementController::class)->group(function () {
    Route::get('announcements/paginate/{params?}', 'paginate');
    Route::get('announcements/latest', 'latest');
});

// Authenticated routes
Route::group(['middleware' => ['auth:sanctum', 'permission']], function () {
    // Auth routes
    Route::controller(AuthController::class)->group(function () {
        Route::get('auth/user', 'user');
        Route::post('auth/logout', 'logout');
    });

    // File upload routes
    Route::controller(FileUploadController::class)->group(function () {
        Route::post('file/upload/{folder}', 'upload');
        Route::delete('file/delete/{folder}', 'delete');
    });

    // Role routes
    Route::controller(RoleController::class)->group(function () {
        Route::get('roles/paginate/{params?}', 'paginate')->name('Role: View Role');
        Route::get('roles/{role}/users', 'users')->name('Role: View Role');
        Route::get('roles/all', 'all')->name('Role: View Role');
        Route::get('roles/{role}', 'get')->name('Role: View Role');
        Route::get('roles', 'roles')->name('Role: View Role');
        Route::post('roles', 'store')->name('Role: Create Role');
        Route::patch('roles/{role}', 'update')->name('Role: Edit/Update Role');
        Route::delete('roles/{role}/{params?}', 'delete')->name('Role: Delete Role');

        Route::get('permissions/paginate/{params?}', 'paginatePermissions')->name('Permission: View Permission');
        Route::get('permissions/all', 'permissions')->name('Permission: View Permission');
    });

    // Announcement routes
    Route::controller(AnnouncementController::class)->group(function () {
        // Route::get('announcements/paginate/{params?}', 'paginate')->name('Announcement: View Announcement');
        Route::get('announcements/all', 'all')->name('Announcement: View Announcement');
        Route::get('announcements/{announcement}', 'get')->name('Announcement: View Announcement');
        Route::post('announcements', 'store')->name('Announcement: Create Announcement');
        Route::patch('announcements/{announcement}', 'update')->name('Announcement: Edit/Update Announcement');
        Route::delete('announcements/{announcement}', 'delete')->name('Announcement: Delete Announcement');
    });

    // Market routes
    Route::controller(MarketController::class)->group(function () {
        Route::get('markets/paginate/{params?}', 'paginate');
        Route::get('markets/all', 'all');
        Route::get('markets/has-merchant', 'hasMerchant')->name('Market: View Market');
        Route::get('markets-code', 'generateCode');
        Route::get('markets/{market}', 'get')->name('Market: View Market');
        Route::get('markets-merchant/{id}', 'isDeleteaAble')->name('Market: View Market');
        Route::post('markets', 'store')->name('Market: Create Market');
        Route::patch('markets/{market}', 'update')->name('Market: Edit/Update Market');
        Route::delete('markets/{market}', 'delete')->name('Market: Delete Market');
    });

    // Merchant routes
    Route::controller(MerchantController::class)->group(function () {
        Route::get('merchants/paginate/{params?}', 'paginate');
        Route::get('merchants/all', 'all');
        Route::get('merchants/{merchant}', 'get')->name('Merchant: View Merchant');
        Route::get('token/merchants', 'getToken');
        Route::post('merchants', 'store')->name('Merchant: Create Merchant');
        Route::patch('merchants/{merchant}', 'update')->name('Merchant: Edit/Update Merchant');
        Route::delete('merchants/{merchant}', 'delete')->name('Merchant: Delete Merchant');
    });

    // User routes
    Route::controller(UserController::class)->group(function () {
        Route::get('users/paginate/{params?}', 'paginate')->name('User: View User');
        Route::get('users/all', 'all')->name('User: View User');
        Route::get('users/{user}', 'get')->name('User: View User');
        Route::post('users', 'store')->name('User: Create User');
        Route::patch('users/{user}', 'update')->name('User: Edit/Update User');
        Route::post('users-password/{id}', 'updatePassword');
        Route::delete('users/{user}', 'delete')->name('User: Delete User');
    });

    // Bet Limit Routes
    Route::controller(BetLimitController::class)->group(function () {
        Route::get('bet-limits/paginate/{params?}', 'paginate')->name('Bet Limit: View Bet Limit');
        Route::get('bet-limits/all', 'all')->name('Bet Limit: View Bet Limit');
        Route::get('bet-limits/{betLimit}', 'get')->name('Bet Limit: View Bet Limit');
        Route::post('bet-limits', 'store')->name('Bet Limit: Create Bet Limit');
        Route::patch('bet-limits/{betLimit}', 'update')->name('Bet Limit: Edit/Update Bet Limit');
        Route::delete('bet-limits/{betLimit}', 'delete')->name('Bet Limit: Delete Bet Limit');
        Route::get('generate-limit-code', 'generateLimitCode')->name('Bet Limit: Generate Limit Code');
    });

    // Currency Routes
    Route::controller(CurrencyController::class)->group(function () {
        Route::get('currencies/paginate/{params?}', 'paginate');
        Route::get('currencies/all', 'all');
        Route::get('currencies/{currency}', 'get')->name('Currency: View Currency');
        Route::post('currencies', 'store')->name('Currency: Create Currency');
        Route::patch('currencies/{currency}', 'update')->name('Currency: Edit/Update Currency');
        Route::delete('currencies/{currency}', 'delete')->name('Currency: Delete Currency');
    });

    // Language Routes
    Route::controller(LanguageController::class)->group(function () {
        Route::get('languages/{language}', 'get')->name('Language: View Language');
        Route::post('languages', 'store')->name('Language: Create Language');
        Route::patch('languages/{language}', 'update')->name('Language: Edit/Update Language');
        Route::delete('languages/{language}', 'delete')->name('Language: Delete Language');
    });

    // Gameplay Routes
    Route::controller(GamePlayController::class)->group(function () {
        Route::get('game-plays/paginate/{params?}', 'paginate')->name('Game Play: View Game Play');
        Route::get('game-plays/all', 'all')->name('Game Play: View Game Play');
        Route::get('game-plays/{gamePlay}', 'get')->name('Game Play: View Game Play');
        Route::post('game-plays', 'store')->name('Game Play: Create Game Play');
        Route::patch('game-plays/{gamePlay}', 'update')->name('Game Play: Edit/Update Game Play');
        Route::delete('game-plays/{gamePlay}', 'delete')->name('Game Play: Delete Game Play');
    });

    // Speical Draw
    Route::controller(SpecialDrawController::class)->group(function () {
        Route::get('special-draws/paginate/{params?}', 'paginate')->name('Special Draw: View Special Draw');
        Route::get('special-draws/all', 'all')->name('Special Draw: View Special Draw');
        Route::get('special-draws/{specialDraw}', 'get')->name('Special Draw: View Special Draw');
        Route::get('special-date/{date}', 'getByDate')->name('Special Draw: View Special Draw');
        // Route::post('special-draws', 'storetransactionsate}', 'getResultByGameId')->name('Result: View Result');
        Route::post('special-draws', 'store')->name('Special Draw: Create Special Draw');
        Route::patch('special-draws/{specialDraw}', 'update')->name('Special Draw: Edit/Update Special Draw');
        Route::delete('special-draws/{specialDraw}', 'delete')->name('Special Draw: Delete Special Draw');
    });

    // Results
    Route::controller(ResultController::class)->group(function () {
        Route::get('results/paginate/{params?}', 'paginate')->name('Result: View Result');
        Route::get('results/all', 'all')->name('Result: View Result');
        Route::get('results/{result}', 'get')->name('Result: View Result');
        Route::get('results-game-id/{id}/{date}', 'getResultByGameId')->name('Result: View Result');
        Route::get('results-last/{gameid}/{status}', 'getLatestResult')->name('Result: View Result');
        Route::post('results', 'store')->name('Result: Create Result');
        Route::post('result-confirm', 'confirm')->name('Result: Create Result');
        Route::patch('results/{result}', 'update')->name('Result: Edit/Update Result');
        Route::delete('results/{result}', 'delete')->name('Result: Delete Result');
        Route::get('results-get/{date}', 'getTwoMonthResult');
    });

    // Tickets
    Route::controller(TicketController::class)->group(function () {
        Route::get('tickets/paginate/{params?}', 'paginate')->name('Ticket: View Ticket');
        Route::get('tickets/all', 'all')->name('Ticket: View Ticket');
        Route::get('tickets/{ticket}', 'get')->name('Ticket: View Ticket');
        Route::get('tickets-status/{status}/{merchantid}', 'checkTicketStatus')->name('Ticket: View Ticket');
        // Route::post('tickets', 'store')->name('Ticket: Create Ticket');
        Route::patch('tickets/{ticket}', 'update')->name('Ticket: Edit/Update Ticket');
        Route::delete('tickets/{ticket}', 'delete')->name('Ticket: Delete Ticket');
        Route::get('live-tickets/paginate', 'paginateLiveTickets')->name('Live Ticket: View Live Tickets');
    });

    // Settinga Routes
    Route::controller(SettingController::class)->group(function () {
        Route::get('settings/paginate/{params?}', 'paginate')->name('Setting: View Setting');
        Route::get('settings/key/{key}', 'getByKey')->name('Setting: View Setting');
        Route::get('settings/all', 'all')->name('Setting: View Setting');
        Route::get('settings/{setting}', 'get')->name('Setting: View Setting');
        Route::post('settings', 'store')->name('Setting: Create Setting');
        Route::patch('settings/{setting}', 'update')->name('Setting: Edit/Update Setting');
        Route::delete('settings/{setting}', 'delete')->name('Setting: Delete Setting');
    });

    // Member routes
    Route::controller(MemberController::class)->group(function () {
        Route::post('members/check-login', 'checkLogin')->name('Member: Check Login');
    });

    // Products
    Route::controller(ProductController::class)->group(function () {
        Route::get('products/paginate/{params?}', 'paginate')->name('Product: View Product');
        Route::get('products/all', 'all')->name('Product: View Product');
        Route::get('products/{products}', 'get')->name('Product: View Product');
        Route::post('products', 'store')->name('Product: Create Product');
        Route::patch('products/{products}', 'update')->name('Product: Edit/Update Product');
        Route::delete('products/{products}', 'delete')->name('Product: Delete Product');
    });

    // Members : I have to create customer controller because someone already created MemberController
    Route::controller(CustomerController::class)->group(function () {
        Route::get('members/paginate/{params?}', 'paginate')->name('Member: View Member');
        Route::get('members/all', 'all')->name('Member: View Member');
        Route::get('members/{member}', 'get')->name('Member: View Member');
        Route::post('members', 'store')->name('Member: Create Member');
        Route::patch('members/{member}', 'update')->name('Member: Edit/Update Member');
        Route::delete('members/{member}', 'delete')->name('Member: Delete Member');
    });

    // Report routes
    Route::controller(ReportController::class)->group(function () {
        Route::get('reports/ticket-lists/paginate/{params?}', 'paginateTickets')->name('Report: View Bet List');
        Route::get('reports/ticket-lists/{ticketId}/slaves/paginate/{params?}', 'paginateTicketSlaves');

        Route::get('reports/merchants/paginate/{params?}', 'paginateWlMerchants')->name('Report: View Merchant Win Lost');
        Route::get('reports/merchants/{merchant}/tickets/{params?}', 'paginateWlMerchantTickets')->name('Report: View Merchant Win Lost');

        Route::get('reports/markets/paginate/{params?}', 'paginateWlMarkets')->name('Report: View Market Win Lost');
        Route::get('reports/markets/{market}/tickets/{params?}', 'paginateWlMarketTickets')->name('Report: View Market Win Lost');

        Route::get('reports/members/paginate/{params?}', 'paginateWlMembers')->name('Report: View Member Win Lost');
        Route::get('reports/members/{member}/tickets/{params?}', 'paginateWlMemberTickets')->name('Report: View Member Win Lost');

        Route::get('reports/transactions/paginate/{params?}', 'paginateTransactions')->name('Report: View Transactions');
        Route::get('reports/transactions/group/paginate/{params?}', 'paginateTransactionsGroupBy')->name('Report: View Transactions');
    });

    // Audit Log
    Route::controller(AuditLogController::class)->group(function () {
        Route::get('audits/paginate/{params?}', 'paginate')->name('Report: View Audit Logs');
        Route::get('audits/models', 'getModels');
    });

    // Login Logs
    Route::controller(LoginLogController::class)->group(function () {
        Route::get('login-logs/paginate/admins/{params?}', 'paginateAdmins')->name('Report: View Admin Login');
        Route::get('login-logs/paginate/members/{params?}', 'paginateMembers')->name('Report: View Member Login');
        Route::get('login-logs/models', 'getModels');
    });

    // Dashboard Statistics
    Route::controller(StatController::class)->group(function () {
        Route::get('stats/this-month-online-members', 'getThisMonthOnlineUsers')->name('Stats: View Monthly Online Users');
        Route::get('stats/this-month-new-members', 'getThisMonthNewMembers')->name('Stats: View this Month New Members');
        Route::get('stats/today-bet-amount', 'getTodayBetAmount')->name('Stats: View Today Bet Amount');
        Route::get('stats/today-members-wl', 'getTodayMembersWL')->name('Stats: View Today Members W/L');
        Route::get('stats/graph-members-online', 'getOnlineStats')->name('Stats: View Online Graph');
        Route::get('stats/graph-bet-amounts', 'getBetAmountStats')->name('Stats: View Bet Amount Graph');
        Route::get('stats/graph-members-wl', 'getMemberWlStats')->name('Stats: View Member WL Amount Graph');
        Route::get('stats/graph-company-bet-distributions', 'getCompanyBetStats')->name('Stats: View Company Bet Distributions Graph');
        Route::get('stats/recent-member-online', 'getRecentMemberOnline')->name('Stats: View Recent Member Online');
        Route::get('stats/recent-transactions', 'getRecentTransactions')->name('Stats: View Recent Transactions');
    });

    // Whitelist IP
    Route::controller(WhitelistIPController::class)->group(function () {
        Route::get('whitelist-ips/paginate/{params?}', 'paginate')->name('Whitelist IP: View Whitelist IP');
        Route::get('whitelist-ips/all', 'all')->name('Whitelist IP: View Whitelist IP');
        Route::get('whitelist-ips/{ip}', 'get')->name('Whitelist IP: View Whitelist IP');
        Route::post('whitelist-ips', 'store')->name('Whitelist IP: Create Whitelist IP');
        Route::patch('whitelist-ips/{ip}', 'update')->name('Whitelist IP: Edit/Update Whitelist IP');
        Route::delete('whitelist-ips/{ip}', 'delete')->name('Whitelist IP: Delete Whitelist IP');
    });
});

// Route::controller(ResultController::class)->group(function () {
//     echo 'test';
//     //Route::get('results/storebyapi', 'storebyapi')->name('Result: Create Result');
// });
Route::get('result/storebyapi', [ResultController::class, 'storeByApi'])->name('Result: Create Result');

Route::post('check-company-user', [CompanyController::class, 'checkValidUser']);
Route::post('check-company-user-data', [CompanyController::class, 'checkValidData']);
Route::post('userLottoLogin', [CompanyController::class, 'checkValidUserApi']);

// Ticket Routes
Route::controller(TicketController::class)->group(function () {
    Route::post('tickets', 'store')->name('Ticket: Create Ticket');
    Route::get('tickets/{ticket_id}', 'getTicket')->name('Ticket: View Ticket');
});

Route::post('member-login', [AuthController::class, 'loginMember']);

/* Merchant  */
Route::get('merchant/{merchant_id}', [MerchantController::class, 'getMerchantById']);
/* Result  */
Route::get('result/latest', [ResultController::class, 'getLatestResult']);
Route::get('result/recent', [ResultController::class, 'getRecentResult']);

// Currency Routes
Route::controller(DateController::class)->group(function () {
    Route::get('dates/all', 'all')->name('Currency: View Currency');
});

/* Ticket  */
//Route::get('ticket', [TicketController::class, 'getTicket'])->name('Ticket: View Ticket');
