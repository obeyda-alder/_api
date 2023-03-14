<?php

namespace App\Models\AddressDetails;

use Illuminate\Database\Eloquent\Model;
use App\Models\AddressDetails\Country;

class City extends Model
{
    protected $table = 'cities';

    protected $fillable = [
        'id',
        'country_id',
        'name_en',
        'name_ar',
    ];

    public function country()
    {
        return $this->belongsTo(Country::class, 'country_id', 'id');
    }
}
