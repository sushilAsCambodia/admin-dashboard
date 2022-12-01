<?php

namespace App\Http\Requests;

use App\Traits\FailedValidation;
use Illuminate\Foundation\Http\FormRequest;

class CurrencyFormRequest extends FormRequest
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
        return [
            'name' => $this->method() === 'POST' ? 'required|unique:currencies,name'
            : 'required|unique:currencies,name,'.$this->currency->id,
            'code' => $this->method() === 'POST' ? 'required|unique:currencies,name'
            : 'required|unique:currencies,name,'.$this->currency->id,
            'symbol' => 'required',
        ];
    }

    public function messages()
    {
        return [
            'name.required' => 'fields is required',
            'code.required' => 'fields is required',
            'symbol.required' => 'fields is required',
        ];
    }
}
