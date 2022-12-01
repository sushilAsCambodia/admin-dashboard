<?php

namespace App\Http\Requests;

use App\Traits\FailedValidation;
use Illuminate\Foundation\Http\FormRequest;

class MerchantFormRequest extends FormRequest
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
        if ($this->is('frontend-api/fetchToken') == true) {
            $rules = [
                'merchant_id' => 'required|exists:merchants,id',
                'secret_key' => 'required|exists:merchants,secret_key',
            ];
        } else {
            $rules = [
                'name' => $this->method() === 'POST' ? 'required'
                : 'required',
                'code' => $this->method() === 'POST' ? 'required|unique:merchants,code'
                : 'required|unique:merchants,code,'.$this->merchant->id,
                'market_id' => 'required',
                'currency_id' => 'required',
                'secret_key' => 'required',
                'credit_limit' => 'required|numeric|min:0',
                'bet_limit_id' => 'required',
                'description' => 'required',
                //'status' => 'required|in_array:Active,Disabled',
            ];
        }

        return $rules;
    }

    public function messages()
    {
        if ($this->is('frontend-api/fetchToken') == true) {
            $messages = [
                'merchant_id.required' => 'Merchant Id Required',
                'merchant_id.exists' => 'Invalid Merchant Id',
                'secret_key.required' => 'Secret Key Required',
                'secret_key.exists' => 'Invalid Secret Key',
            ];
        } else {
            $messages = [
                'credit_limit.min' => 'must be postive number',
                'market_id.required' => 'Fields is required',
                'currency_id.required' => 'Fields is required',
                'secret_key.required' => 'Fields is required',
                'bet_limit_id.required' => 'Fields is required',
                'description.required' => 'Fields is required',
                'name.required' => 'Fields is required',
                'code.required' => 'Fields is required',
            ];
        }

        return $messages;
    }
}
