<?php

namespace App\Models\Entities;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use App\Models\Entities\RelationsType;
use App\Models\User;

class Operations extends Model
{
    use HasFactory;

    protected $table = 'operations';

    protected $fillable = [
        'id',
        'type_en',
        'type_ar',
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
}
