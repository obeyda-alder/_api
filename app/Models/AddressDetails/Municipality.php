<?php

namespace App\Models\AddressDetails;

use Illuminate\Database\Eloquent\Model;
use App\Models\AddressDetails\City;
use App\Models\AddressDetails\Neighborhood;

class Municipality extends Model
{
    protected $table = 'municipalities';

    public $timestamps = false;

    public function city()
    {
        return $this->belongsTo(City::class, 'municipality_city', 'id');
    }

    public function neighborhoods()
    {
        return $this->hasMany(Neighborhood::class, 'neighborhood_municipality_key', 'municipality_key');
    }
}
