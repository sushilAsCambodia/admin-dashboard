<?php

namespace App\Http\Controllers;

use App\Http\Requests\LoginFormRequest;
use App\Models\Language;
use App\Models\LoginLog;
use App\Models\Member;
use App\Models\Wallet;
use App\Services\AuthService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class AuthController extends Controller
{
    public function __construct(private AuthService $authService)
    {
    }

    public function login(LoginFormRequest $request)
    {
        return $this->authService->login($request->all());
    }

    public function user()
    {
        return $this->authService->user();
    }

    public function logout()
    {
        return $this->authService->logout();
    }

    public function loginMember(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'customer_name' => 'required',
            'customer_id' => 'required|alpha_dash',
            'merchant_id' => 'required|exists:merchants,id,status,Active',
        ]);

        if ($validator->fails()) {
            //return response()->json(['Validation Error.' => $validator->errors()]);
            return response()->json(['message' => $validator->messages(), 'success' => false, 'messageId' => 406], 200);
        }

        $merchantId = intval($request->merchant_id);

        $email = $request->email;
        $userName = $request->customer_name;
        $user = null;
        $user = Member::whereCustomerId($request->customer_id)->whereMerchantId($merchantId)->first();
        //dd($user);
        if (! empty($request->language)) {
            $language = Language::select('id')->whereLocale($request->language)->first();
            if (empty($language)) {
                $langID = 1;
            } else {
                $langID = $language->id;
            }
        }

        $userData['language_id'] = $langID;

        if (! $user) {
            //save user data
            $userData['name'] = $userName;
            $userData['email'] = $email;
            $userData['customer_id'] = $request->customer_id;
            $userData['customer_name'] = $request->customer_name;
            $userData['merchant_id'] = $merchantId;

            $user = Member::create($userData);
            $user['user_id'] = $user->id;
            $memberId = $user['user_id'];
            unset($user['id']);
        } else {
            $memberId = $user->id;
            if ($user->language_id != $langID) {
                Member::whereId($user->id)->update(['language_id' => $langID]);
            }
        }
        Member::whereId($memberId)->update(['last_login' => Carbon::now()->format('Y-m-d H:i:s'), 'login_ip' => request()->ip(), 'online_status' => 'online']);
        //initialize customer wallet
        $customerWallet = Wallet::whereMemberId($memberId)->whereMerchantId($merchantId)->first();
        if (! $customerWallet) {
            Wallet::create([
                'member_id' => $memberId,
                'merchant_id' => $merchantId,
                'amount' => 0,
            ]);
        }

        LoginLog::create([
            'user_id' => $memberId,
            'user_type' => Member::class,
            'ip_address' => request()->ip(),
            'user_agent' => request()->userAgent(),
        ]);

        $userData = Member::with(['language', 'merchant.currency', 'wallet', 'merchant.betLimit', 'merchant.betLimit.limitSettings'])->whereCustomerId($request->customer_id)->whereMerchantId($merchantId)->first();
        DB::table('personal_access_tokens')->where('tokenable_id', $userData->id)->delete();
        $oldTokens = DB::select('select * from `personal_access_tokens` where (`last_used_at` is null or `last_used_at` < DATE_SUB(NOW(), INTERVAL 30 MINUTE))');

        if (! empty($oldTokens)) {
            foreach ($oldTokens as $values) {
                DB::table('personal_access_tokens')->where('id', $values->id)->delete();
            }
        }
        Member::whereId($memberId)->update(['last_login' => Carbon::now()->format('Y-m-d H:i:s'), 'login_ip' => request()->ip(), 'online_status' => 'online']);
        $userData->token = $userData->createToken($userData->id)->plainTextToken;
        DB::table('personal_access_tokens')->where('tokenable_id', $memberId)->update(['last_used_at' => Carbon::now()->format('Y-m-d H:i:s')]);
        $data['data'] = $userData;
        $data['message'] = 'Loggedin user successfully';
        $data['success'] = true;

        return response()->json($data, 200);
    }

    public function logoutMember(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'member_id' => 'required|exists:members,id',
        ]);

        $memberId = $request->member_id;
        if ($validator->fails()) {
            return response()->json(['message' => $validator->messages()->first(), 'status' => false, 'messageId' => 406], 200);
        }
        $user = Member::whereId($memberId)->first();
        $user->tokens()->delete();
        if (empty($user)) {
            $data['message'] = 'Invalid Member';
            $data['status'] = false;
            $data['messageId'] = 200;
        } else {
            Member::whereId($memberId)->update(['last_login' => Carbon::now()->format('Y-m-d H:i:s'), 'login_ip' => request()->ip(), 'online_status' => 'offline']);
            $data['message'] = 'Loggedout user successfully';
            $data['status'] = true;
            $data['messageId'] = 200;
        }

        return response()->json($data, 200);
    }

    public function getUserData(Request $request)
    {
        $memberId = auth('sanctum')->user()->id;
        $user = Member::whereId($memberId)->first();

        if (! empty($request->language)) {
            $language = Language::select('id')->whereLocale($request->language)->first();
            if (empty($language)) {
                $langID = 1;
            } else {
                $langID = $language->id;
            }
            if ($user->language_id != $langID) {
                Member::whereId($user->id)->update(['language_id' => $langID]);
            }
        }
        $user = Member::with(['language', 'merchant.currency', 'wallet', 'merchant.betLimit', 'merchant.betLimit.limitSettings'])
                        ->whereId($memberId)->first();

        $data['message'] = 'User Details fetched successfully';
        $data['status'] = true;
        $data['messageId'] = 200;
        $data['data'] = $user;

        return response()->json($data, 200);
    }
}
