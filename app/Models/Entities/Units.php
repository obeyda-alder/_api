<?php

namespace App\Models\Entities;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Models\Entities\UnitType;
use App\Models\User;
use Carbon\Carbon;

class Units extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'unit_generation_history';

    protected $fillable = [
        'id',
        'unit_code',
        'unit_type_id',
        'price',
        'unit_value',
        'add_by',
        'status',
        'deleted_at',
        'created_at',
        'updated_at',
    ];
    public function getCreatedAtAttribute( $value ) {
        return Carbon::parse($value)->format('Y-m-d H:i:s');
    }
    public function getUpdatedAtAttribute( $value ) {
        return Carbon::parse($value)->format('Y-m-d H:i:s');
    }
    public function unit_type()
    {
        return $this->hasMany(UnitType::class, 'id', 'unit_type_id');
    }
    public function user()
    {
        return $this->belongsTo(User::class, 'add_by');
    }
}
