<?php

namespace App\Models\Entities;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Models\Entities\OperationType;
use App\Models\User;

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
        'operation_type_id',
        'add_by_user_id',
        'percentage',
        'deleted_at',
        'created_at',
        'updated_at',
    ];

    public function operationType()
    {
        return $this->hasMany(OperationType::class, 'id','operation_type_id');
    }
    public function user()
    {
        return $this->belongsTo(User::class, 'add_by_user_id');
    }
}
