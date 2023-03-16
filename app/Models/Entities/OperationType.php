<?php

namespace App\Models\Entities;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use App\Models\Entities\Operations;
use App\Models\User;

class OperationType extends Model
{
    use HasFactory;

    protected $table = 'operation_type';

    protected $fillable = [
        'id',
        'type_en',
        'type_ar',
        'operation_id',
        'add_by_user_id',
    ];

    public $timestamps = false;

    public function operation()
    {
        return $this->hasMany(Operations::class, 'id','operation_id');
    }
    public function user()
    {
        return $this->belongsTo(User::class, 'add_by_user_id');
    }
}
