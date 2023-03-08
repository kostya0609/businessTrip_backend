<?php
namespace App\Modules\BusinessTrip\Model;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;


class CostUnit extends Model {

    protected $table = 'l_business_trip_cost_units';

    public function costLists():Hasmany{
        return $this->hasMany(CostList::class, 'cost_id', 'id');
    }

}
