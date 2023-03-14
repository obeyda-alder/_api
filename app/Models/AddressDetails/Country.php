<?php

namespace App\Models\AddressDetails;

use App\Models\AddressDetails\City;
use Illuminate\Database\Eloquent\Model;

class Country extends Model
{
    protected $table = 'countries';

    protected $fillable = [
        'id',
        'sortname',
        'name_en',
        'name_ar',
        'flag',
        'code',
        'currency',
        'currency_icon',
        'status',
    ];

    public function Cities()
    {
        return $this->hasMany(City::class, 'country_id', 'id');
    }
}
