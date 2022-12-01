<?php

namespace App\Http\Requests;

use App\Traits\FailedValidation;
use Illuminate\Foundation\Http\FormRequest;

class TicketFormRequest extends FormRequest
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
        if ($this->is('frontend-api/ticket') == true
        || $this->is('frontend-api/betList') == true
        || $this->is('frontend-api/betListReject') == true
        || $this->is('frontend-api/betListWinning') == true
        ) {
            $rules = [
                'member_id' => 'required|numeric|gt:0',
            ];
        } elseif ($this->is('frontend-api/fetchBettingReport') == true) {
            $rules = [
                'sdate' => 'date_format:Y-m-d',
                'edate' => 'date_format:Y-m-d',
            ];
        } elseif (
            $this->is('frontend-api/fetchBettingReport') == true ||
            $this->is('frontend-api/fetchBettingReportByCustomerId') == true ||
            $this->is('frontend-api/fetchBettingReportByRefNumber') == true ||
            $this->is('frontend-api/fetchBettingReportByDate') == true ||
             $this->is('frontend-api/fetchWinningReport') == true
        ) {
            $rules = [];
        } else {
            $rules = [
                'member_id' => 'required|numeric|gt:0|exists:members,id',
                'merchant_id' => 'required|exists:merchants,id',
                // 'options' => 'required|array',
                'game_dates' => 'required|array',
            ];
        }

        return $rules;
    }
}
