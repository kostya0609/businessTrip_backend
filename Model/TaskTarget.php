<?php
namespace App\Modules\BusinessTrip\Model;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TaskTarget extends Model {

    protected $table = 'l_business_trip_task_target';

    public function task():BelongsTo{
        return $this->belongsTo(Task::class, 'task_id', 'id');
    }

    public function dot():BelongsTo{
        return $this->belongsTo(TaskDot::class, 'dot_id', 'id');
    }

    public function target():BelongsTo{
        return $this->belongsTo(Target::class, 'target_id', 'id');
    }




}
