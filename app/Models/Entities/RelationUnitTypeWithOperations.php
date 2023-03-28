<?php

namespace App\Models\Entities;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use App\Models\Entities\Operations;
use App\Models\User;
use App\Models\Entities\UnitType;

class RelationUnitTypeWithOperations extends Model
{
    use HasFactory;

    protected $table = 'relation_unit_type_with_operations';

    protected $fillable = [
        'id',
        'from_unit_type_id',
        'to_unit_type_id',
        'operation_id',
        'add_by_user_id',
    ];

    public $timestamps = false;

    public function from_unit_type()
    {
        return $this->belongsTo(UnitType::class,'from_unit_type_id','id');
    }
    public function to_unit_type()
    {
        return $this->belongsTo(UnitType::class,'to_unit_type_id','id');
    }
    public function operation()
    {
        return $this->belongsTo(Operations::class);
    }
    public function user()
    {
        return $this->belongsTo(User::class, 'add_by_user_id');
    }
}
