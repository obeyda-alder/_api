<?php

namespace App\Models\Entities;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Carbon\Carbon;

class Config extends Model
{
    use HasFactory;

    protected $table = 'ince_transfer_config';

    protected $fillable = [
        'id',
        'type',
        'name',
        'currency',
        'price',
        'created_at',
        'updated_at',
    ];

    public function getCreatedAtAttribute( $value ) {
        return Carbon::parse($value)->format('Y-m-d H:i:s');
    }
    public function getUpdatedAtAttribute( $value ) {
        return Carbon::parse($value)->format('Y-m-d H:i:s');
    }
}
