<?php

namespace App\Models\Entities;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use App\Models\User;
use App\Models\Entities\UnitType;
use App\Models\Entities\UnitTypesSafe;

class UserUnits extends Model
{
    use HasFactory;

    protected $table = 'user_units';

    protected $fillable = [
        'id',
        'unit_type_id',
        'user_id'
    ];

    public $timestamps = false;

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }
    public function unit_type_safe()
    {
        return $this->hasOne(UnitTypesSafe::class);
    }
}
