<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use OwenIt\Auditing\Contracts\Auditable;

class BetLimit extends Model implements Auditable
{
    use HasFactory, SoftDeletes;
    use \OwenIt\Auditing\Auditable;

    protected $table = 'bet_limits';

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'name', 'description', 'currency_id',
    ];

    /**
     * The attributes that should be cast to native types.
     */
    protected $casts = [
        'created_at', 'updated_at', 'deleted_at',
    ];

    /*
     * The limits that belong to this Currency
     */
    public function currency()
    {
        return $this->belongsTo(Currency::class);
    }

    public function merchants()
    {
        return $this->hasMany(Merchant::class);
    }

    /*
     * Limit Settings
     */
    public function limitSettings()
    {
        return $this->hasMany(LimitSetting::class);
    }
}
