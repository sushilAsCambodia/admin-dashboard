<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use OwenIt\Auditing\Contracts\Auditable;

class Setting extends Model implements Auditable
{
    use HasFactory;
    use \OwenIt\Auditing\Auditable;

    protected $guarded = ['id'];

    public function value(): Attribute
    {
        return Attribute::make(
            get:fn ($value) => $this->getValue($value),
            set:fn ($value) => is_array($value) ? json_encode($value) : $value,
        );
    }

    public function getValue($value)
    {
        if (is_array(json_decode($value))) {
            return json_decode($value);
        }

        if (is_numeric($value)) {
            return is_int($value) ? (int) $value : (float) $value;
        }

        return $value;
    }
}
