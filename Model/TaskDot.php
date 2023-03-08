<?php
namespace App\Modules\BusinessTrip\Model;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class TaskDot extends Model {

    protected $table = 'l_business_trip_task_dot';

    public function task():BelongsTo{
        return $this->belongsTo(Task::class, 'task_id', 'id');
    }

    public function targets():Hasmany{
        return $this->hasMany(TaskTarget::class, 'dot_id', 'id');
    }

    public function city():BelongsTo{
        return $this->belongsTo(City::class, 'city_id', 'id');
    }

}
