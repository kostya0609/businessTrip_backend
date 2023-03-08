<?php

namespace App\Modules\BusinessTrip\Model;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;


class File extends Model{
    protected $table = 'l_business_trip_files';

    public function task():BelongsTo{
        return $this->belongsTo(Task::class, 'task_id', 'id');
    }
}
