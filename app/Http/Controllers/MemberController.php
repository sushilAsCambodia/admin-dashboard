<?php

namespace App\Http\Controllers;

use App\Models\Member;
use App\Models\User;
use App\Models\Wallet;
use App\Services\MemberService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Spatie\Permission\Models\Role;

class MemberController extends Controller
{
    public function __construct(private MemberService $memberService)
    {
    }

    public function register(Request $request)
    {
        $validatedData = $request->validate([
            'name' => 'required|max:55',
            'email' => 'email|required|unique:users',
            'password' => 'required|confirmed',
        ]);

        $validatedData['password'] = bcrypt($request->password);
        $validatedData['role_id'] = 2;
        $validatedData['created_by'] = 1;

        $user = User::create($validatedData);

        $accessToken = $user->createToken('authToken')->accessToken;

        return response(['user' => $user, 'access_token' => $accessToken]);
    }

    public function login(Request $request)
    {
        // $loginData = $request->validate([
        //     'email' => 'required',
        //     'password' => 'required'
        // ]);

        // if (!auth()->attempt($loginData)) {
        //     return response(['message' => 'Invalid Credentials']);
        // }

        // $accessToken = auth()->user()->createToken('authToken')->accessToken;

        // return response(['user' => auth()->user(), 'access_token' => $accessToken]);

        $validator = Validator::make($request->all(), [
            'customer_id' => 'required',
            'enterprise_id' => 'required|exists:companies,id',
            'email' => [
                'required',
                Rule::unique('users')->ignore($request->customer_id, 'customer_id'),
            ],
            'user_name' => 'required',
        ]);
        if ($validator->fails()) {
            return (new BaseController)->sendError('Validation Error.', $validator->errors());
        }

        $customerId = intval($request->customer_id);
        $enterpriseId = intval($request->enterprise_id);
        $email = $request->email;
        $userName = $request->user_name;
        $user = null;
        $user = User::whereCustomerId($customerId)
            ->whereEnterpriseId($enterpriseId)
            ->select(
                'id as user_id',
                'name',
                'email',
                'customer_id',
                'enterprise_id',
                'role_id',
                'name',
            )
            ->first();

        if (! $user) {
            //save user data
            $userData['name'] = $userName;
            $userData['email'] = $email;
            $userData['customer_id'] = $customerId;
            $userData['enterprise_id'] = $enterpriseId;
            $userData['role_id'] = Role::first()->id;
            $userData['password'] = bcrypt('Welcom@123');
            $userData['password'] = bcrypt('Welcom@123');
            $userData['user_type'] = 'frontend';

            $user = User::create($userData);
            $user['user_id'] = $user->id;
            unset($user['id']);
        }
        //initialize customer wallet
        $customerWallet = Wallet::whereCustomerId($customerId)->whereCompanyId($enterpriseId)->first();
        if (! $customerWallet) {
            Wallet::create([
                'customer_id' => $customerId,
                'company_id' => $enterpriseId,
                'amount' => 0,
            ]);
        }
        $userData = $user->toArray();
        $currencySymbol = $user->company->currency->symbol;
        $userData['currency_symbol'] = $currencySymbol;
        $userData['min_bet_amount'] = $user->company->min_bet_value;
        $userData['max_bet_amount'] = $user->company->max_bet_value;

        return (new BaseController)->sendResponse($userData, 'login user successfully');
    }

    public function profile()
    {
        $user_data = auth()->user();

        return response()->json([
            'status' => true,
            'message' => 'User data',
            'data' => $user_data,
        ]);
    }

    public function logout(Request $request)
    {
        // get token value
        $token = $request->user()->token();

        // revoke this token value
        $token->revoke();

        return response()->json([
            'status' => true,
            'message' => 'User logged out successfully',
        ]);
    }

    public function identifyUser(Request $request)
    {
        // code...
        $validator = Validator::make($request->all(), [
            'customer_id' => 'required',
            'enterprise_id' => 'required',

        ]);

        if ($validator->fails()) {
            return (new BaseController)->sendError('Validation Error.', $validator->errors());
        }

        $customerId = $request->customer_id;
        $enterpriseId = $request->enterprise_id;
        $user = User::whereCustomerId($customerId)->whereEnterpriseId($enterpriseId)->first();

        //save user dump data
        if (! $user) {
            $user = User::create([
                'customer_id' => $customerId,
                'enterprise_id' => $enterpriseId,
                'name' => 'Customer '.$customerId,
                'email' => 'Customer'.$customerId.'@gmail.com',
                'password' => bcrypt('Customer@123'),
                'role_id' => Role::first()->id,
                'created_by' => @Auth::id(),

            ]);
        }

        return (new BaseController)->sendResponse($user, 'indentifying user successfully');
    }

    public function checkLogin(Request $request)
    {
        return $this->memberService->checkLogin($request->all());
    }

    public function all()
    {
        return response()->json(Member::orderBy('id', 'desc')->get(), 200);
    }
}
