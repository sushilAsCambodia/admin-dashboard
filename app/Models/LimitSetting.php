<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use OwenIt\Auditing\Contracts\Auditable;

class LimitSetting extends Model implements Auditable
{
    use HasFactory, SoftDeletes;
    use \OwenIt\Auditing\Auditable;

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $guarded = ['id'];

    /*
     * The limits that belong to this Currency
     */
    public function currencies()
    {
        return $this->belongsTo(Currency::class);
    }

    /*
     * The limits that belong to this Currency
     */
    public function betLimit()
    {
        return $this->belongsTo(BetLimit::class);
    }
}
