<?php

namespace App\Http\Requests;

use App\Rules\IPAddressRule;
use App\Traits\FailedValidation;
use Illuminate\Foundation\Http\FormRequest;

class WhitelistIPFormRequest extends FormRequest
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
            'address' => [
                'required',
                new IPAddressRule,
                $this->method() === 'POST' ? 'unique:whitelist_ips,address' : 'unique:whitelist_ips,address,'.$this->id,
            ],
            'remarks' => 'required',
        ];
    }
}
