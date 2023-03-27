<?php

namespace App\Models\Entities;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Models\Entities\RelationUnitTypeWithOperations;
use App\Models\User;
use Carbon\Carbon;

class Categories extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'categories';

    protected $fillable = [
        'id',
        'name',
        'code',
        'unit_min_limit',
        'unit_max_limit',
        'value_in_price',
        'status',
        'add_by_user_id',
        'percentage',
        'deleted_at',
        'created_at',
        'updated_at',
    ];

    public function getCreatedAtAttribute( $value ) {
        return Carbon::parse($value)->format('d/m/Y h:i');
    }
    public function getUpdatedAtAttribute( $value ) {
        return Carbon::parse($value)->format('d/m/Y h:i');
    }
    public function user()
    {
        return $this->belongsTo(User::class, 'add_by_user_id');
    }
}
