<?php

namespace App\Rules;

use App\Models\BetLimit;
use Illuminate\Contracts\Validation\Rule;

class LimitCodeCurrencyCombinationExist implements Rule
{
    /**
     * Create a new rule instance.
     *
     * @return void
     */
    public function __construct()
    {
        //
    }

    /**
     * Determine if the validation rule passes.
     *
     * @param  string  $attribute
     * @param  mixed  $value
     * @return bool
     */
    public function passes($attribute, $value)
    {
        // if (request()->method() === 'POST') {
        return ! BetLimit::where($attribute, $value)->where('name', request()->name)->whereNotNull('deleted_at')->first();
        // } else {
        //     return !BetLimit::where($attribute, $value)->where('name', request()->name)->whereNotNull('deleted_at')->first();
        // }
    }

    /**
     * Get the validation error message.
     *
     * @return string
     */
    public function message()
    {
        return 'The validation error message.';
    }
}
