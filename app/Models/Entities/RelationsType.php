<?php

namespace App\Models\Entities;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use App\Models\User;
use App\Models\Entities\UnitType;
use App\Models\Entities\Operations;

class RelationsType extends Model
{
    use HasFactory;

    protected $table = 'relations_type';

    protected $fillable = [
        'id',
        'relation_type',
        'user_type',
        'add_by_user_id',
    ];

    public $timestamps = false;

    public function unit_Type()
    {
        return $this->hasMany(UnitType::class,'relation_id','id');
    }
    public function operations()
    {
        return $this->hasMany(Operations::class,'relation_id', 'id');
    }
    public function user()
    {
        return $this->belongsTo(User::class, 'add_by_user_id');
    }
}
