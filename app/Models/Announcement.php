<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Announcement extends Model
{
    use HasFactory, SoftDeletes;
    //use \OwenIt\Auditing\Auditable;

    protected $auditExclude = ['content'];

    protected $guarded = ['id'];

    protected $casts = [
        'content' => 'array',
    ];

    protected function content(): Attribute
    {
        return Attribute::make(
            get:fn ($value) => json_decode($value),
            set:fn ($value) => json_encode($value),
        );
    }
}
