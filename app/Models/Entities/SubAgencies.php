<?php

namespace Modules\CMS\Entities;

use Astrotomic\Translatable\Contracts\Translatable as TranslatableContract;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;
use Astrotomic\Translatable\Translatable;
use App\Models\AddressDetails\City;
use App\Models\AddressDetails\Country;
use App\Models\AddressDetails\Municipality;
use App\Models\AddressDetails\Neighborhood;

class SubAgencies extends Model implements TranslatableContract
{
    use HasFactory, SoftDeletes, Translatable;

    protected $table = 'sub_agencies';

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
        'agent_type',
        'phone_number',
        'master_agent_id',
        'status',
        'deleted_at',
        'created_at',
        'updated_at',
    ];


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
