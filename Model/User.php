<?php
namespace App\Modules\BusinessTrip\Model;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class User extends Model{
    protected $table = 'b_user';
    protected $primaryKey = 'ID';

    public function getFullNameAttribute():string{
        return $this->LAST_NAME.' '.$this->NAME.' '.$this->SECOND_NAME;
    }

    public function logs(){
        return $this->hasMany(Log::class, 'user_id', 'ID');
    }

    public function getPhoto(){
        $res = DB::table('b_file')->where('ID','=' , $this->PERSONAL_PHOTO)->first();

        if($res){
            $path = '/upload/'.$res->SUBDIR.'/'.$res->FILE_NAME;
            return  $path;
        }
    }

    public function roles(){
        return $this->belongsToMany(Role::class, 'l_business_trip_role_user', 'user_id', 'role_id');
    }

    public function additionalRightsUsers(){
        return $this->morphedByMany(User::class,'entity', 'l_business_trip_additional_rights')->withPivot('full_access');
    }

    public function additionalRightsDepartments(){
        return $this->morphedByMany(Department::class,'entity', 'l_business_trip_additional_rights')->withPivot('full_access');;
    }

}
