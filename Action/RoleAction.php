<?php

namespace App\Modules\BusinessTrip\Action;
use App\Modules\BusinessTrip\Model\Role;

class RoleAction{
    public static function get(){
        return Role::all()
            ->map(function($item){
                return [
                    'value' => $item->id,
                    'label' => $item->note,
                    'name'  => $item->name
                ];
            });
    }

    public static function list(){

        return Role::all()->flatMap(function($role){
            return [
                $role->name =>
                    $role->users->map(function($user) use ($role)
                    {

                        $departments = $user->additionalRightsDepartments;
                        $individuals = $user->additionalRightsUsers;
                        return [
                            'role_id'     => $role->id,
                            'user_id'     => $user->ID,
                            'name'        => $user->full_name,
                            'departments' => ($departments)?$departments->map(function($item)
                            {
                                return [
                                    'id'            => $item->ID,
                                    'name'          => $item->NAME,
                                    'full_access'   => $item->pivot->full_access
                                ];
                            }):[],
                            'individuals' => ($individuals)?$individuals->map(function($item)
                            {
                                return [
                                    'id'            => $item->ID,
                                    'name'          => $item->full_name,
                                    'full_access'   => $item->pivot->full_access
                                ];
                            }):[],
                        ];
                    })
            ];
        });
    }

}
