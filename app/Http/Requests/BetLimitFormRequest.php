<?php

namespace App\Http\Requests;

use App\Rules\LimitCodeExist;
use App\Traits\FailedValidation;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class BetLimitFormRequest extends FormRequest
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
        $name = request()->name;

        return [
            'name' => ['required', new LimitCodeExist],
            'currency_id' => [
                'required',
                $this->method() === 'POST' ? Rule::unique('bet_limits')
                    ->where(function ($query) use ($name) {
                        $query->where('name', '=', $name);
                    }) : Rule::unique('bet_limits')
                    ->where(function ($query) use ($name) {
                        $query->where('name', '=', $name);
                    })->ignore($this->betLimit->id, 'id'),
            ],
            'description' => 'required',
            'limit_settings' => 'required|array',
            'limit_settings.*.game_play_id' => 'required|integer',
            'limit_settings.*.big_min_bet' => 'required|integer|min:0',
            'limit_settings.*.big_max_bet' => 'required|integer|min:0|gt:limit_settings.*.big_min_bet',
            'limit_settings.*.small_min_bet' => 'required|integer|min:0|',
            'limit_settings.*.small_max_bet' => 'required|integer|min:0|gt:limit_settings.*.small_min_bet',
            'limit_settings.*.three_c_min_bet' => 'required|integer|min:0|:',
            'limit_settings.*.three_c_max_bet' => 'required|integer|min:0|gt:limit_settings.*.three_c_min_bet',
            'limit_settings.*.three_a_min_bet' => 'required|integer|min:0|',
            'limit_settings.*.three_a_max_bet' => 'required|integer|min:0|gt:limit_settings.*.three_a_min_bet',
            'limit_settings.*.game_limit_big' => 'required|integer|min:0',
            'limit_settings.*.game_limit_small' => 'required|integer|min:0',
            'limit_settings.*.game_limit_three_a' => 'required|integer|min:0',
            'limit_settings.*.game_limit_three_c' => 'required|integer|min:0',

        ];
    }

    public function messages()
    {
        return [
            'name.required' => 'Name is required',
            'description.required' => 'Description is required',
            'currency_id.required' => 'Currency is required',
            // 'currency_id.uniqulimit_settings.*small_max_bet.required' => 'Small max value is required',
            'limit_settings.*.three_c_min_bet.required' => 'three A min value is required',
            'limit_settings.*.three_c_max_bet.required' => 'three A max value is required',
            'limit_settings.*.three_a_min_bet.required' => 'three C min value is required',
            'limit_settings.*.three_a_max_bet.required' => 'three C max value is required',

            'limit_settings.*.game_limit_big.required' => 'Game Limit Big min value is required',
            'limit_settings.*.game_limit_small.required' => 'Game Limit Small min value is required',
            'limit_settings.*.game_limit_three_a.required' => 'Game Limit 3A min value is required',
            'limit_settings.*.game_limit_three_c.required' => 'Game Limit 3C min value is required',

            'limit_settings.*.big_min_bet.min' => 'Big min bet value must be postive number',
            'limit_settings.*.big_max_bet.min' => 'Big max bet value must be postive number',
            'limit_settings.*.small_min_bet.min' => 'Small min bet value must be postive number',
            'limit_settings.*.small_max_bet.min' => 'Small max bet value must be postive number',
            'limit_settings.*.three_c_min_bet.min' => 'three A min bet value must be postive number',
            'limit_settings.*.three_c_max_bet.min' => 'three A max bet value must be postive number',
            'limit_settings.*.three_a_min_bet.min' => 'three C min bet value must be postive number',
            'limit_settings.*.three_a_max_bet.min' => 'three C max bet value must be postive number',
            'limit_settings.*.game_limit_big.min' => 'Game Limit Big min bet value must be postive number',
            'limit_settings.*.game_limit_small.min' => 'Game Limit Small min bet value must be postive number',
            'limit_settings.*.game_limit_three_a.min' => 'Game Limit 3A min bet value must be postive number',
            'limit_settings.*.game_limit_three_c.min' => 'Game Limit 3C min bet value must be postive number',

            'limit_settings.*.big_max_bet.gt' => 'Big max bet value must greater than min value',
            'limit_settings.*.small_max_bet.gt' => 'Small max bet value must greater than min value',
            'limit_settings.*.three_c_max_bet.gt' => 'three A max bet value must greater than min value',
            'limit_settings.*.three_a_max_bet.gt' => 'three C max bet value must greater than min value',

        ];
    }
}
