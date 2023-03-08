<?php

namespace App\Modules\BusinessTrip\Model;;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Log extends Model
{
    protected $table = 'l_business_trip_logs';
    protected $dates = ['date'];

    public function task():BelongsTo{
        return $this->belongsTo(Task::class, 'task_id', 'id');
    }

    public function user():BelongsTo{
        return $this->belongsTo(User::class, 'user_id', 'ID');
    }

    public function setLog($task_id, $user_id, $event){
        $this->task_id   = $task_id;
        $this->user_id   = $user_id;
        $this->date      = Carbon::now();
        $this->event     = $event;
        $this->save();
    }

}
