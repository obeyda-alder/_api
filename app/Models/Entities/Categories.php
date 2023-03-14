<?php

namespace Modules\CMS\Entities;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;

class Categories extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'categories';

    protected $fillable = [
        'id',
        'name',
        'code',
        'from',
        'to',
        'price',
        'status',
        'deleted_at',
        'created_at',
        'updated_at',
    ];
}
