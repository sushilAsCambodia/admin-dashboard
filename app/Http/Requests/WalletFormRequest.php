<?php

namespace App\Http\Requests;

use App\Traits\FailedValidation;
use Illuminate\Foundation\Http\FormRequest;

class WalletFormRequest extends FormRequest
{
    use FailedValidation;

    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool
     */
    public function authorize()
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, mixed>
     */
    public function rules()
    {
        $rules = [];
        if ($this->is('api/wallet/get-amount') == true) {
            $rules = [
                'customer_id' => 'required',
                'merchant_id' => 'required|exists:merchants,id',

            ];
        }
        if ($this->is('api/wallet/fetchWalletBalance') == true) {
            $rules = [
                'customer_id_list' => 'required|array',
                'merchant_id' => 'required|exists:merchants,id',

            ];
        }
        if ($this->is('frontend-api/getTransactionDetailsByID') == true) {
            $rules = [
                'transaction_id_list' => 'array',
                'customer_id_list' => 'array',
                'merchant_id' => 'required|exists:merchants,id',

            ];
        }
        if ($this->is('api/wallet/updateWallet') == true) {
            $rules = [
                'amount' => 'required',
                'customer_id' => 'required',
                'merchant_id' => 'required|exists:merchants,id',
                'mode' => 'required|in:Debit,Credit',
                // 'transaction_type.in' => 'flag must be Debit or Credit',

            ];
        }

        return $rules;
    }

    // public function messages(){

    //     if($this->is('frontend-api/getTransactionDetailsByID') == true){
    //         $message =  [

    //          ];
    //          return $message;
    //      }
    // }
}
