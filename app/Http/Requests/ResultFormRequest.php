<?php

namespace App\Http\Requests;

use App\Traits\FailedValidation;
use Illuminate\Foundation\Http\FormRequest;

class ResultFormRequest extends FormRequest
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
        if ($this->is('frontend-api/getBetTips') == true) {
            $rules = [
                'company' => 'required|array',
                'number' => 'required|string|min:3|max:4',
            ];
        } else {
            $rules = [
                //'reference_number'  => 'required',
                // 'fetching_date'  => 'required',
                'game_play_id' => 'required|numeric',
                'prize1' => 'required|numeric|digits:4|min:0',
                'prize2' => 'required|numeric|digits:4|min:0',
                'prize3' => 'required|numeric|digits:4|min:0',
                'special1' => 'required|numeric|digits:4|min:0',
                'special2' => 'required|numeric|digits:4|min:0',
                'special3' => 'required|numeric|digits:4|min:0',
                'special4' => 'required|numeric|digits:4|min:0',
                'special5' => 'required|numeric|digits:4|min:0',
                'special6' => 'required|numeric|digits:4|min:0',
                'special7' => 'required|numeric|digits:4|min:0',
                'special8' => 'required|numeric|digits:4|min:0',
                'special9' => 'required|numeric|digits:4|min:0',
                'special10' => 'required|numeric|digits:4|min:0',
                'consolation1' => 'required|numeric|digits:4|min:0',
                'consolation2' => 'required|numeric|digits:4|min:0',
                'consolation3' => 'required|numeric|digits:4|min:0',
                'consolation4' => 'required|numeric|digits:4|min:0',
                'consolation5' => 'required|numeric|digits:4|min:0',
                'consolation6' => 'required|numeric|digits:4|min:0',
                'consolation7' => 'required|numeric|digits:4|min:0',
                'consolation8' => 'required|numeric|digits:4|min:0',
                'consolation9' => 'required|numeric|digits:4|min:0',
                'consolation10' => 'required|numeric|digits:4|min:0',
            ];
        }

        return $rules;
    }

    public function messages()
    {
        $messages = [];
        if ($this->is('frontend-api/getBetTips') != true) {
            $messages = [
                'prize1.required' => 'All prizes values are required',
                'prize2.required' => 'All prizes values are required',
                'prize3.required' => 'All prizes values are required',
                'special1.required' => 'All prizes values are required',
                'special2.required' => 'All prizes values are required',
                'special3.required' => 'All prizes values are required',
                'special4.required' => 'All prizes values are required',
                'special5.required' => 'All prizes values are required',
                'special6.required' => 'All prizes values are required',
                'special7.required' => 'All prizes values are required',
                'special8.required' => 'All prizes values are required',
                'special9.required' => 'All prizes values are required',
                'special10.required' => 'All prizes values are required',
                'consolation1.required' => 'All prizes values are required',
                'consolation2.required' => 'All prizes values are required',
                'consolation3.required' => 'All prizes values are required',
                'consolation4.required' => 'All prizes values are required',
                'consolation5.required' => 'All prizes values are required',
                'consolation6.required' => 'All prizes values are required',
                'consolation7.required' => 'All prizes values are required',
                'consolation8.required' => 'All prizes values are required',
                'consolation9.required' => 'All prizes values are required',
                'consolation10.required' => 'All prizes values are required',

                'prize1.integer' => 'All prizes values must be postive number',
                'prize2.integer' => 'All prizes values must be postive number',
                'prize3.integer' => 'All prizes values must be postive number',
                'special1.integer' => 'All prizes values must be postive number',
                'special2.integer' => 'All prizes values must be postive number',
                'special3.integer' => 'All prizes values must be postive number',
                'special4.integer' => 'All prizes values must be postive number',
                'special5.integer' => 'All prizes values must be postive number',
                'special6.integer' => 'All prizes values must be postive number',
                'special7.integer' => 'All prizes values must be postive number',
                'special8.integer' => 'All prizes values must be postive number',
                'special9.integer' => 'All prizes values must be postive number',
                'special10.integer' => 'All prizes values must be postive number',
                'consolation1.integer' => 'All prizes values must be postive number',
                'consolation2.integer' => 'All prizes values must be postive number',
                'consolation3.integer' => 'All prizes values must be postive number',
                'consolation4.integer' => 'All prizes values must be postive number',
                'consolation5.integer' => 'All prizes values must be postive number',
                'consolation6.integer' => 'All prizes values must be postive number',
                'consolation7.integer' => 'All prizes values must be postive number',
                'consolation8.integer' => 'All prizes values must be postive number',
                'consolation9.integer' => 'All prizes values must be postive number',
                'consolation10.integer' => 'All prizes values must be postive number',

                'prize1.digits' => 'All prizes values are must be 4 digits',
                'prize2.digits' => 'All prizes values are must be 4 digits',
                'prize3.digits' => 'All prizes values are must be 4 digits',
                'special1.digits' => 'All prizes values are must be 4 digits',
                'special2.digits' => 'All prizes values are must be 4 digits',
                'special3.digits' => 'All prizes values are must be 4 digits',
                'special4.digits' => 'All prizes values are must be 4 digits',
                'special5.digits' => 'All prizes values are must be 4 digits',
                'special6.digits' => 'All prizes values are must be 4 digits',
                'special7.digits' => 'All prizes values are must be 4 digits',
                'special8.digits' => 'All prizes values are must be 4 digits',
                'special9.digits' => 'All prizes values are must be 4 digits',
                'special10.digits' => 'All prizes values are must be 4 digits',
                'consolation1.digits' => 'All prizes values are must be 4 digits',
                'consolation2.digits' => 'All prizes values are must be 4 digits',
                'consolation3.digits' => 'All prizes values are must be 4 digits',
                'consolation4.digits' => 'All prizes values are must be 4 digits',
                'consolation5.digits' => 'All prizes values are must be 4 digits',
                'consolation6.digits' => 'All prizes values are must be 4 digits',
                'consolation7.digits' => 'All prizes values are must be 4 digits',
                'consolation8.digits' => 'All prizes values are must be 4 digits',
                'consolation9.digits' => 'All prizes values are must be 4 digits',
                'consolation10.digits' => 'All prizes values are must be 4 digits',

                'prize1.min' => 'All prizes values are must be positive number',
                'prize2.min' => 'All prizes values are must be positive number',
                'prize3.min' => 'All prizes values are must be positive number',
                'special1.min' => 'All prizes values are must be positive number',
                'special2.min' => 'All prizes values are must be positive number',
                'special3.min' => 'All prizes values are must be positive number',
                'special4.min' => 'All prizes values are must be positive number',
                'special5.min' => 'All prizes values are must be positive number',
                'special6.min' => 'All prizes values are must be positive number',
                'special7.min' => 'All prizes values are must be positive number',
                'special8.min' => 'All prizes values are must be positive number',
                'special9.min' => 'All prizes values are must be positive number',
                'special10.min' => 'All prizes values are must be positive number',
                'consolation1.min' => 'All prizes values are must be positive number',
                'consolation2.min' => 'All prizes values are must be positive number',
                'consolation3.min' => 'All prizes values are must be positive number',
                'consolation4.min' => 'All prizes values are must be positive number',
                'consolation5.min' => 'All prizes values are must be positive number',
                'consolation6.min' => 'All prizes values are must be positive number',
                'consolation7.min' => 'All prizes values are must be positive number',
                'consolation8.min' => 'All prizes values are must be positive number',
                'consolation9.min' => 'All prizes values are must be positive number',
                'consolation10.min' => 'All prizes values are must be positive number',
            ];
        }

        return $messages;
    }
}
