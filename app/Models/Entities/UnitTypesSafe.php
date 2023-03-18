<?php

namespace App\Models\Entities;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use App\Models\User;
use App\Models\Entities\UnitType;

class UnitTypesSafe extends Model
{
    use HasFactory;

    protected $table = 'unit_types_safe';

    protected $fillable = [
        'id',
        'unit_type_count',
        'user_id',
        'unit_type_id',
        'status'
    ];

    public $timestamps = false;

    public function unitType()
    {
        return $this->hasMany(UnitType::class, 'id', 'unit_type_id');
    }
    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }
}
