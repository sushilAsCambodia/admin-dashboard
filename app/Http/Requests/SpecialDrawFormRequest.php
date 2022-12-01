<?php

namespace App\Http\Requests;

use App\Traits\FailedValidation;
use Illuminate\Foundation\Http\FormRequest;

class SpecialDrawFormRequest extends FormRequest
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
        $draw_date = request()->draw_date;

        return [
            'draw_date' => $this->method() === 'POST' ? 'required|unique:special_draws' : 'required',
            'game_plays' => 'required|array',
        ];
    }

    public function messages()
    {
        return [
            'draw_date.required' => 'Select Draw Date',
            'game_plays.required' => 'Select Game Play',
            'draw_date.unique' => 'Draw date already exist',
        ];
    }
}
