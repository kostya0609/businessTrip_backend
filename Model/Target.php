<?php
namespace App\Modules\BusinessTrip\Model;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class target extends Model {

    protected $table = 'l_business_trip_targets';

    public function taskTargets():Hasmany{
        return $this->hasMany(TaskTarget::class, 'target_id', 'id');
    }

}
