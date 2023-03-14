<?php

namespace Modules\CMS\Entities;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;

class SubAgenciesTranslation extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'sub_agencies_translate';

    protected $fillable = [
        'id',
        'title',
        'description',
        'locale',
        'image',
        'sub_agencies_id',
        'deleted_at',
        'created_at',
        'updated_at',
    ];
}
