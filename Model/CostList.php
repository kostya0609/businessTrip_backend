<?php
namespace App\Modules\BusinessTrip\Model;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CostList extends Model {

    protected $table = 'l_business_trip_cost_lists';

    public function task():BelongsTo{
        return $this->belongsTo(Task::class, 'task_id', 'id');
    }

    public function costUnit():BelongsTo{
        return $this->belongsTo(CostUnit::class, 'cost_id', 'id');
    }

}
