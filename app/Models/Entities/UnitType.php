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
        'continued',
    ];

    public $timestamps = false;

}
