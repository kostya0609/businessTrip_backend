<?php

namespace App\Modules\BusinessTrip\Action;

use App\Modules\BusinessTrip\Model\User;
use App\Modules\BusinessTrip\Model\Department;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class Verifications{
    public static function checkTaskAccess($model, $user_id){
        $usersIds = [$user_id];
        $model = $model->whereIn('responsible_id', $usersIds);
        return $model;
    }

    public static function userDepartment($user_id){
        $depId = DB::table('b_user')
            ->join('b_utm_user', 'b_user.ID', '=', 'b_utm_user.VALUE_ID')
            ->select('b_user.ID','b_user.ACTIVE', 'b_user.NAME', 'b_user.LAST_NAME',
                'b_user.SECOND_NAME', 'b_user.XML_ID', 'b_utm_user.VALUE_INT as DEPARTMENT')
            ->where([['b_utm_user.FIELD_ID', 41], ['b_user.ID', $user_id], ['ACTIVE', 'Y']])
            ->first()->DEPARTMENT;
        return Department::find($depId);
    }
}

