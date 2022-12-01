<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use OwenIt\Auditing\Contracts\Auditable;

class Market extends Model implements Auditable
{
    use HasFactory, SoftDeletes;
    use \OwenIt\Auditing\Auditable;

    protected $guarded = ['id'];

    public function oddSettings()
    {
        return $this->hasMany(OddSetting::class);
    }

    public function merchants()
    {
        return $this->hasMany(Merchant::class);
    }
}
