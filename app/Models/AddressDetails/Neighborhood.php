<?php

namespace App\Models\AddressDetails;

use Illuminate\Database\Eloquent\Model;
use App\Models\AddressDetails\Municipality;

class Neighborhood extends Model
{
    protected $table = 'neighborhoods';

    public $timestamps = false;

    public function municipality()
    {
        return $this->belongsTo(Municipality::class, 'neighborhood_municipality_key', 'municipality_key');
    }
}
