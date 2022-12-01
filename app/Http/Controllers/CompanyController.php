<?php

namespace App\Http\Controllers;

use App\Models\Company;
use App\Models\Merchant;
use Illuminate\Http\Request;

class CompanyController extends Controller
{
    protected $redirectValidUrl;

    protected $redirectInvalidUrl;

    public function __construct()
    {
        $this->redirectValidUrl = env('APP_ENV') === 'local' ? 'http://127.0.0.1:3000/api/login' : 'http://kk-lotto.com/api/login';
        $this->redirectInvalidUrl = env('APP_ENV') === 'local' ? 'http://127.0.0.1:3000/api/logout' : 'http://kk-lotto.com/api/logout';
    }

    public function checkValidUserApi(Request $request)
    {
        $token = empty($request['token']) ? $request->bearerToken() : $request['token'];

        if (empty($token) || empty($request['merchant_id'])) {
            return redirect($this->redirectInvalidUrl);
        }
        $companyData['id'] = $request['merchant_id'];
        $companyData['token'] = $token;
        // $checkValidData = Company::where($companyData)->first();
        $companyData['customer_id'] = $request['customer_id'];
        $companyData['user_name'] = $request['user_name'];
        $companyData['language'] = $request['language'];

        // if (! empty($checkValidData)) {
        return view('redirect')->with($companyData);
        // } else {
        //     return redirect($redirectInvalidUrl);
        // }
    }

    public function checkValidUser(Request $request)
    {
        $token = ! empty($request['token']) ? $this->request()->bearerToken() : '';
        if (empty($token) || empty($request['enterprise_id'])) {
            return redirect($this->redirectInvalidUrl);
        }
        $companyData['id'] = $request['enterprise_id'];
        $companyData['token'] = $token;
        // $checkValidData = Company::where($companyData)->first();
        $companyData['customer_id'] = $request['customer_id'];
        $companyData['user_name'] = $request['user_name'];
        $companyData['language'] = $request['language'];
        // if (! empty($checkValidData)) {
        return view('redirect')->with($companyData);
        // } else {
        //     return redirect($redirectInvalidUrl);
        // }
    }

    public function checkValidData(Request $request)
    {
        if (empty($request['token']) || empty($request['enterprise_id'])) {
            return redirect($this->redirectInvalidUrl);
        }

        $companyData['id'] = $request['enterprise_id'];
        $companyData['token'] = $request['token'];
        $checkValidData = Merchant::where($companyData)->first();
        if (! empty($checkValidData)) {
            return redirect($this->redirectValidUrl);
        } else {
            return redirect($this->redirectInvalidUrl);
        }
    }
}
