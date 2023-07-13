<?php

namespace App\Modules\BusinessTrip\Model;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Role extends Model{
    protected $table = 'l_business_trip_role';

    public function users():BelongsToMany{
        return $this->belongsToMany(User::class,'l_business_trip_role_user', 'role_id', 'user_id');
    }
}
