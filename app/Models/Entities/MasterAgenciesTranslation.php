<?php

namespace Modules\CMS\Entities;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;

class MasterAgenciesTranslation extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'master_agencies_translate';

    protected $fillable = [
        'id',
        'title',
        'description',
        'locale',
        'image',
        'master_agencies_id',
        'deleted_at',
        'created_at',
        'updated_at',
    ];
}
