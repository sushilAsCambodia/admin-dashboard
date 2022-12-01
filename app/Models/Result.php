<?php

namespace App\Models;

use App\Traits\SerializeDate;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use OwenIt\Auditing\Contracts\Auditable;

class Result extends Model implements Auditable
{
    use HasFactory, SoftDeletes, SerializeDate;
    use \OwenIt\Auditing\Auditable;

    protected $guarded = ['id'];

    public function merchant()
    {
        return $this->belongsTo(Merchant::class);
    }

    public function product()
    {
        return $this->belongsTo(Product::class);
    }

    public function gamePlay()
    {
        return $this->belongsTo(GamePlay::class);
    }
}
