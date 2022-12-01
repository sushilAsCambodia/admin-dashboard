<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Laravel\Sanctum\HasApiTokens;
use OwenIt\Auditing\Contracts\Auditable;

class Member extends Model implements Auditable
{
    use HasFactory, SoftDeletes, HasApiTokens;
    use \OwenIt\Auditing\Auditable;

    protected $guarded = ['id'];

    public function merchant()
    {
        return $this->belongsTo(Merchant::class);
    }

    public function language()
    {
        return $this->belongsTo(Language::class);
    }

    public function wallet()
    {
        return $this->hasOne(Wallet::class, 'member_id', 'id');
    }

    public function logins()
    {
        return $this->morphMany(LoginLog::class, 'user');
    }
}
