<?php

namespace App\Models;

use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Models\AddressDetails\City;
use App\Models\Entities\MasterAgencies;
use Tymon\JWTAuth\Contracts\JWTSubject;
use App\Models\Entities\MoneySafe;
use App\Models\Entities\UnitsSafe;
class User extends Authenticatable implements JWTSubject
{
    use HasApiTokens, HasFactory, Notifiable, SoftDeletes;

    protected $fillable = [
        'id',
        'type',
        'name',
        'username',
        'phone_number',
        'email',
        'password',
        'image',
        'verification_code',
        'status',
        'country_id',
        'city_id',
        'municipality_id',
        'neighborhood_id',
        'registration_type',
        'social_id',
        'deleted_at',
        'created_at',
        'updated_at',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected $casts = [
        'email_verified_at' => 'datetime',
    ];
    public function isApproved()
    {
        if( !in_array( $this->type,  config('custom.users_type') ) )
            return false;

        return $this->status == 'ACTIVE';
    }
    public function isRegistered()
    {
        return true;
        return $this->registered != 0;
    }
    public function isVerified()
    {
        if( !in_array( $this->type, config('custom.users_type') ) )
            return false;

        // if( static::$availableTypes[ $this->type ]['require_activation'] )
        // {
        //     return $this->verification_code == 'VERIFIED';
        // }
        return true;
    }
    public function scopeNotRegistered( $query )
    {
        return $query->whereNull('email');
    }

    public function scopeThatActive( $query )
    {
        return $query->where(function ($q) {
            $q->where('status', 'ACTIVE');
        });
    }

    public function scopeThatVerified( $query )
    {
        return $query->where(function ($q) {
            $q->where('verification_code', 'VERIFIED');
        });
    }

    public function scopeOfType( $query, $type )
    {
        // if( !array_key_exists( "{$type}", static::$availableTypes ) )
        //     return $query->where('id', 0);

        return $query->where(function ($q) use ( $type ) {
            $q->where('type', "{$type}");
        });
    }

    public function scopeNotOfType( $query, $type )
    {
        // if( !array_key_exists( "{$type}", static::$availableTypes ) )
        //     return $query->where('id', 0);

        return $query->where(function ($q) use ( $type ) {
            $q->where('type', '!=', "{$type}");
        });
    }
    public function city()
    {
        return $this->belongsTo(City::class, 'city_id', 'id');
    }
    public function money()
    {
        return $this->hasOne(MoneySafe::class, 'user_id', 'id');
    }
    public function unit()
    {
        return $this->hasOne(UnitsSafe::class, 'user_id', 'id');
    }
    public function masterAgencies()
    {
        return $this->hasMany(MasterAgencies::class, 'user_id', 'id');
    }
    public function getJWTIdentifier()
    {
        return $this->getKey();
    }
    public function getJWTCustomClaims()
    {
        return [];
    }

}
