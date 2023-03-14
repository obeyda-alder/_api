<?php

namespace Modules\CMS\Entities;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;

class Units extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'units';

    protected $fillable = [
        'id',
        'name',
        'code',
        'price',
        'user_id',
        'category_id',
        'status',
        'deleted_at',
        'created_at',
        'updated_at',
    ];
}
