<?php

namespace App\Models\Entities;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use App\Models\User;
use App\Models\Entities\UserUnits;

class UnitTypesSafe extends Model
{
    use HasFactory;

    protected $table = 'unit_types_safe';

    protected $fillable = [
        'id',
        'unit_code',
        'unit_type_count',
        'user_units_id',
        'user_id',
        'status'
    ];

    public $timestamps = false;

    public function user_unit()
    {
        return $this->hasMany(UserUnits::class, 'id', 'user_units_id');
    }
    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }
}
