<?php

namespace App\Models\Entities;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use App\Models\User;
use Carbon\Carbon;

class PackingOrder extends Model
{
    use HasFactory;

    protected $table = 'packing_order_requests';

    protected $fillable = [
        'id',
        'order_from_user_id',
        'quantity',
        'order_status',
        'created_at',
        'updated_at',
    ];

    public function order_from_user_id()
    {
        return $this->belongsTo(User::class, 'order_from_user_id');
    }
    public function getCreatedAtAttribute( $value ) {
        return Carbon::parse($value)->format('d/m/Y h:i');
    }
    public function getUpdatedAtAttribute( $value ) {
        return Carbon::parse($value)->format('d/m/Y h:i');
    }
}
