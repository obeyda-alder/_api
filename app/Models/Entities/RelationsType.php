<?php

namespace App\Models\Entities;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use App\Models\User;

class RelationsType extends Model
{
    use HasFactory;

    protected $table = 'relations_type';

    protected $fillable = [
        'id',
        'type',
        'add_by_user_id',
    ];

    public $timestamps = false;

    public function user()
    {
        return $this->belongsTo(User::class, 'add_by_user_id');
    }
}
