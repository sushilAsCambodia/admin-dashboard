<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Laravel\Sanctum\HasApiTokens;
use OwenIt\Auditing\Contracts\Auditable;

class Merchant extends Model implements Auditable
{
    use HasFactory, HasApiTokens, SoftDeletes;
    use \OwenIt\Auditing\Auditable;

    protected $guarded = ['id'];

    public function market()
    {
        return $this->belongsTo(Market::class);
    }

    public function currency()
    {
        return $this->belongsTo(Currency::class);
    }

    public function betLimit()
    {
        return $this->belongsTo(BetLimit::class);
    }

    public function members()
    {
        return $this->hasMany(Member::class);
    }

    public function tickets()
    {
        return $this->hasMany(Ticket::class);
    }

    public function wallet()
    {
        return $this->hasOne(Wallet::class);
    }

    public function user()
    {
        return $this->belongsToMany(User::class);
    }
}
