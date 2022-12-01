<?php

namespace App\Rules;

use Illuminate\Contracts\Validation\Rule;

class IPAddressRule implements Rule
{
    private $message = 'Invalid IP address format.';

    private $pass = true;

    private $inWhitelists = false;

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
        $parts = explode('.', $value);

        info($parts);

        if (! count($parts) || count($parts) !== 4) {
            $this->pass = false;
        }

        if (! is_numeric($parts[0])) {
            $this->pass = false;
        }

        foreach ($parts as $part) {
            if (is_numeric($part) && ! ((int) $part <= 255 && (int) $part >= 0)) {
                $this->pass = false;
                $this->message = 'IP address part must be between 0 and 255 inclusive';
            }

            if (! is_numeric($part) && $part !== '*') {
                $this->pass = false;
            }
        }

        // foreach (WhitelistIP::ALLOWED_IPS as $ip) {
        //     if (strpos($value, $ip) !== false) {
        //         $this->inWhitelists = true;
        //     }
        // }

        return $this->pass;
    }

    /**
     * Get the validation error message.
     *
     * @return string
     */
    public function message()
    {
        return $this->message;
    }
}
