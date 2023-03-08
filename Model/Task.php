<?php
namespace App\Modules\BusinessTrip\Model;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;


class Task extends Model {

    protected $table = 'l_business_trip_tasks';

    protected $dates = ['date_start', 'date_final', 'date-created'];

    public function targets():Hasmany{
        return $this->hasMany(TaskTarget::class, 'task_id', 'id');
    }

    public function dots():Hasmany{
        return $this->hasMany(TaskDot::class, 'task_id', 'id');
    }

    public function costLists():Hasmany{
        return $this->hasMany(CostList::class, 'task_id', 'id');
    }

    public function logs():HasMany{
        return $this->hasMany(Log::class, 'task_id', 'id');
    }

    public function files():HasMany{
        return $this->hasMany(File::class, 'task_id', 'id');
    }



}
