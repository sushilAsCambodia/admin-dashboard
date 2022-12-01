<?php

namespace App\Http\Requests;

use App\Rules\MarketNameExist;
use App\Traits\FailedValidation;
use Illuminate\Foundation\Http\FormRequest;

class MarketFormRequest extends FormRequest
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
            'name' => $this->method() === 'POST' ? ['required', 'alpha', 'max:2', new MarketNameExist]
            : 'required|alpha|max:2',
            'status' => 'required',
            'description' => 'required',
            'odd_settings' => 'required|array',
            'odd_settings.*.game_play_id' => 'required|numeric|min:0',
            'odd_settings.*.big_first' => 'required|integer|min:0',
            'odd_settings.*.big_second' => 'required|integer|min:0',
            'odd_settings.*.big_third' => 'required|integer|min:0',
            'odd_settings.*.big_special' => 'required|integer|min:0',
            'odd_settings.*.big_consolation' => 'required|integer|min:0',
            'odd_settings.*.small_first' => 'required|integer|min:0',
            'odd_settings.*.small_second' => 'required|integer|min:0',
            'odd_settings.*.small_third' => 'required|integer|min:0',
            'odd_settings.*.three_c_first' => 'required|integer|min:0',
            'odd_settings.*.three_c_second' => 'required|integer|min:0',
            'odd_settings.*.three_c_third' => 'required|integer|min:0',
            'odd_settings.*.three_a_first' => 'required|integer|min:0',
            'odd_settings.*.rebate_4d' => 'required|integer|min:0|max_digits:2',
            'odd_settings.*.rebate_3d' => 'required|integer|min:0|max_digits:2',
        ];
    }

    public function messages()
    {
        return [
            'odd_settings.*.big_first.required' => 'Big 1st value must be postive number',
            'odd_settings.*.big_second.required' => 'Big 2nd value must be postive number',
            'odd_settings.*.big_third.required' => 'Big 3rd value must be postive number',
            'odd_settings.*.big_special.required' => 'Big special value must be postive number',
            'odd_settings.*.big_consolation.required' => 'Big consolation value must be postive number',
            'odd_settings.*.small_first.required' => 'Small 1st value must be postive number',
            'odd_settings.*.small_second.required' => 'Small 2nd value must be postive number',
            'odd_settings.*.small_third.required' => 'Small 3rd value must be postive number',
            'odd_settings.*.three_c_first.required' => '3A 1st value must be postive number',
            'odd_settings.*.three_c_second.required' => '3C 1st value must be postive number',
            'odd_settings.*.three_c_third.required' => '3C 2nd value must be postive number',
            'odd_settings.*.three_a_first.required' => '3C 3rd value must be postive number',
            'odd_settings.*.rebate_4d.required' => '4D rebate value must be postive number',
            'odd_settings.*.rebate_3d.required' => '3D rebate value must be postive number',

            'odd_settings.*.big_first.min' => 'Big 1st value must be postive number',
            'odd_settings.*.big_second.min' => 'Big 2nd value must be postive number',
            'odd_settings.*.big_third.min' => 'Big 3rd value must be postive number',
            'odd_settings.*.big_special.min' => 'Big special value must be postive number',
            'odd_settings.*.big_consolation.min' => 'Big consolation value must be postive number',
            'odd_settings.*.small_first.min' => 'Small 1st value must be postive number',
            'odd_settings.*.small_second.min' => 'Small 2nd value must be postive number',
            'odd_settings.*.small_third.min' => 'Small 3rd value must be postive number',
            'odd_settings.*.three_c_first.min' => '3A 1st value must be postive number',
            'odd_settings.*.three_c_second.min' => '3C 1st value must be postive number',
            'odd_settings.*.three_c_third.min' => '3C 2nd value must be postive number',
            'odd_settings.*.three_a_first.min' => '3C 3rd value must be postive number',
            'odd_settings.*.rebate_4d.min' => '4D rebate value must be postive number',
            'odd_settings.*.rebate_3d.min' => '3D rebate value must be postive number',
        ];
    }
}
