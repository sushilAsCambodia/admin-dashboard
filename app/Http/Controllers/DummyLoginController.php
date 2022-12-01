<?php

namespace App\Http\Controllers;

use App\Models\Company;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class DummyLoginController extends Controller
{
    public function __construct()
    {
    }

    public function index()
    {
        $data = [];

        return view('dummy/index', compact('data'));
    }

    public function checkValid(Request $request)
    {
        $validatedData = Validator::make($request->all(), [
            'user_name' => ['required'],
            'password' => ['required'],
        ]);
        $data['username'] = $request['user_name'];
        $data['status'] = 'enabled';

        $result = Company::where($data)->first();

        if (! empty($result)) {
            if (md5($request['password']) == $result->password) {
                return $this->sendResponse($result->token, 'Token retrieved successfully.');
            } else {
                return $this->sendError('Invalid Password');
            }
        } else {
            return $this->sendError('Invalid Credential');
        }
    }

    public function checkValidId(Request $request)
    {
        $validatedData = Validator::make($request->all(), [
            'customer_id' => ['required'],
            'enterprise_id' => ['required'],
            'token' => ['required'],

        ]);

        if (! $validatedData->fails()) {
            $redirectValidUrl = 'http://localhost:3000/api/login';
            $redirectInvalidUrl = 'http://localhost:3000/api/logout';

            $data['customer_id'] = $request['customer_id'];
            $data['enterprise_id'] = $request['enterprise_id'];
            $data['status'] = 'enabled';
            $result = User::where($data)->first();

            if (! empty($result)) {
                $companyData['id'] = $request['enterprise_id'];
                $companyData['token'] = $request['token'];
                $checkValidData = Company::where($companyData)->first();
                if (! empty($checkValidData)) {
                    return redirect($redirectValidUrl);
                } else {
                    return redirect($redirectInvalidUrl);
                }
            }

            $request->session()->flash('message', 'Invalid credential');
            $request->session()->flash('alert-class', 'alert-danger');

            return redirect()->back();
        } else {
            $validatedData->errors()->add('', 'There is something error, users details does not checks');
        }
    }

    /**
     * success response method.
     *
     * @return \Illuminate\Http\Response
     */
    public function sendResponse($result, $message, $more_fields = [])
    {
        $response = [
            'success' => true,
            'data' => $result,
            'message' => $message,
        ];
        if (! empty($more_fields)) {
            $response = array_merge($response, $more_fields);
        }

        return response()->json($response, 200);
    }

    /**
     * return error response.
     *
     * @return \Illuminate\Http\Response
     */
    public function sendError($error, $errorMessages = [], $code = 200)
    {
        $response = [
            'success' => false,
            'message' => $error,
        ];
        if (! empty($errorMessages)) {
            $response['data'] = $errorMessages;
        }

        return response()->json($response, $code);
    }
}
