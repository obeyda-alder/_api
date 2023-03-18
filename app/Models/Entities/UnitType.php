<?php

namespace App\Models\Entities;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use App\Models\User;
use App\Models\Entities\RelationsType;
use App\Models\Entities\UnitTypesSafe;

class UnitType extends Model
{
    use HasFactory;

    protected $table = 'unit_type';

    protected $fillable = [
        'id',
        'type',
        'relation_id',
        'add_by_user_id',
    ];

    public $timestamps = false;

    public function relation()
    {
        return $this->hasMany(RelationsType::class, 'id', 'relation_id');
    }
    public function user()
    {
        return $this->belongsTo(User::class, 'add_by_user_id');
    }
    public function unit_type_safe()
    {
        return $this->hasOne(UnitTypesSafe::class);
    }
}
