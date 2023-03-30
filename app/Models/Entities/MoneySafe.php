<?php

namespace App\Models\Entities;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use App\Models\User;
use App\Models\Entities\Config;

class MoneySafe extends Model
{
    use HasFactory;

    protected $table = 'money_safe';

    protected $fillable = [
        'id',
        'amount',
        'user_id',
        'config_currency_id',
        'status',
    ];

    public $timestamps = false;

    public function config_currency()
    {
        return $this->belongsTo(Config::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }
}
