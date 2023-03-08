<?php
namespace App\Modules\BusinessTrip\Model;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class City extends Model {

    protected $table = 'l_business_trip_cities';

    public function taskDots():Hasmany{
        return $this->hasMany(TaskDot::class, 'city_id', 'id');
    }

    public function getLimitKmAttribute(){
        $limit_km = 15;
        $population = $this->attributes['population'] / 1000;
        if($population > 100 && $population <= 200) $limit_km = 20;
            elseif ($population > 200 && $population <= 500) $limit_km = 30;
                elseif ($population > 500) $limit_km = 40;
        return $limit_km;
    }
}
