<?php

namespace App\Models\Entities;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use App\Models\User;

class MoneySafe extends Model
{
    use HasFactory;

    protected $table = 'money_safe';

    protected $fillable = [
        'id',
        'amount',
        'user_id',
        'status',
    ];

    public $timestamps = false;

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }
}
