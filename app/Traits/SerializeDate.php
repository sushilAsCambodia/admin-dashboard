<?php

namespace App\Traits;

use Carbon\Carbon;
use DateTimeInterface;

trait SerializeDate
{
    protected function serializeDate(DateTimeInterface $date)
    {
        return Carbon::instance($date)->toDateTimeString();
    }
}
