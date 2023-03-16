<?php

namespace App\Models\Entities;

use Astrotomic\Translatable\Contracts\Translatable as TranslatableContract;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Models\AddressDetails\City;
use App\Models\AddressDetails\Country;
use App\Models\AddressDetails\Municipality;
use App\Models\AddressDetails\Neighborhood;
use Astrotomic\Translatable\Translatable;
use App\Models\User;
use App\Models\Entities\SubAgencies;

class MasterAgencies extends Model implements TranslatableContract
{
    use HasFactory, SoftDeletes, Translatable;

    protected $table = 'master_agencies';

    public $translatedAttributes = ['title', 'description', 'image'];

    protected $fillable = [
        'id',
        'first_name',
        'last_name',
        'email',
        'country_id',
        'city_id',
        'municipality_id',
        'neighborhood_id',
        'desc_address',
        'latitude',
        'longitude',
        'iban',
        'iban_name',
        'iban_type',
        'phone_number',
        'user_id',
        'has_sub_agent',
        'sub_agent_count',
        'status',
        'deleted_at',
        'created_at',
        'updated_at',
    ];

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }
    public function sub_agent()
    {
        return $this->hasMany(SubAgencies::class, 'master_agent_id', 'id');
    }
    public function country()
    {
        return $this->belongsTo(Country::class);
    }
    public function city()
    {
        return $this->belongsTo(City::class);
    }
    public function municipality()
    {
        return $this->belongsTo(Municipality::class);
    }
    public function neighborhood()
    {
        return $this->belongsTo(Neighborhood::class);
    }
}
