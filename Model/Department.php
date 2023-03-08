<?php
namespace App\Modules\BusinessTrip\Model;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;


class Department extends Model {
    protected $table = 'b_iblock_section';
    protected $primaryKey = 'ID';

    public function tasks():HasMany{
        return $this->hasMany(Task::class, 'department_id', 'ID');
    }

}
