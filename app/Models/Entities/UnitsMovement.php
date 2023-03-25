<?php

namespace App\Models\Entities;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Models\User;
use App\Models\Entities\MoneySafe;

class UnitsMovement extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'units_movement';

    protected $fillable = [
        'id',
        'unit_code',
        'transfer_type',
        'quantity',
        'to_user_id',
        'from_user_id',
        'status',
        'deleted_at',
        'created_at',
        'updated_at',
    ];

    public function to_user()
    {
        return $this->belongsTo(User::class, 'to_user_id');
    }
    public function from_user()
    {
        return $this->belongsTo(User::class, 'from_user_id');
    }
}
