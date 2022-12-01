<?php

namespace App\Http\Requests;

use App\Traits\FailedValidation;
use Illuminate\Foundation\Http\FormRequest;

class OddSettingFormRequest extends FormRequest
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
            '*.market_id' => 'required|numeric|min:0',
            '*.game_play_id' => 'required|numeric|min:0',
            '*.big_first' => 'required|numeric|min:0',
            '*.big_second' => 'required|numeric|min:0',
            '*.big_third' => 'required|numeric|min:0',
            '*.big_special' => 'required|numeric|min:0',
            '*.big_consolation' => 'required|numeric|min:0',
            '*.small_first' => 'required|numeric|min:0',
            '*.small_second' => 'required|numeric|min:0',
            '*.small_third' => 'required|numeric|min:0',
            '*.three_c_first' => 'required|numeric|min:0',
            '*.three_c_second' => 'required|numeric|min:0',
            '*.three_c_third' => 'required|numeric|min:0',
            '*.three_a_first' => 'required|numeric|min:0',
            '*.rebate_4d' => 'required|numeric|min:0|digits:2',
            '*.rebate_3d' => 'required|numeric|min:0|digits:2',
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
            'odd_settings.*.three_c_first.required' => 'three A 1st value must be postive number',
            'odd_settings.*.three_c_second.required' => 'three C 1st value must be postive number',
            'odd_settings.*.three_c_third.required' => 'three C 2nd value must be postive number',
            'odd_settings.*.three_a_first.required' => 'three C 3rd value must be postive number',
            'odd_settings.*.rebate_4d.required' => 'four D rebate value must be postive number',
            'odd_settings.*.rebate_3d.required' => 'three D rebate value must be postive number',

            'odd_settings.*.big_first.min' => 'Big 1st value must be postive number',
            'odd_settings.*.big_second.min' => 'Big 2nd value must be postive number',
            'odd_settings.*.big_third.min' => 'Big 3rd value must be postive number',
            'odd_settings.*.big_special.min' => 'Big special value must be postive number',
            'odd_settings.*.big_consolation.min' => 'Big consolation value must be postive number',
            'odd_settings.*.small_first.min' => 'Small 1st value must be postive number',
            'odd_settings.*.small_second.min' => 'Small 2nd value must be postive number',
            'odd_settings.*.small_third.min' => 'Small 3rd value must be postive number',
            'odd_settings.*.three_c_first.min' => 'three A 1st value must be postive number',
            'odd_settings.*.three_c_second.min' => 'three C 1st value must be postive number',
            'odd_settings.*.three_c_third.min' => 'three C 2nd value must be postive number',
            'odd_settings.*.three_a_first.min' => 'three C 3rd value must be postive number',
            'odd_settings.*.rebate_4d.min' => 'four D rebate value must be postive number',
            'odd_settings.*.rebate_3d.min' => 'three D rebate value must be postive number',
        ];
    }
}
