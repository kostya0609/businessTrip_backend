<?php

namespace App\Modules\BusinessTrip\Controllers;

use App\Modules\BusinessTrip\Model\Role;
use App\Modules\BusinessTrip\Model\User;
use App\Modules\BusinessTrip\Action\RoleAction;
use Illuminate\Http\Request;

class RoleController{
    public function add(Request $request){
        if(!$request->user_id || !$request->role_id){
            return response()->json(['status' => 'error']);
        }
        $role = Role::find($request->role_id);
        $role->users()->attach($request->user_id);

        return response()->json(['status' => 'success']);
    }

    public function delete(Request $request){
        if(!$request->user_id || !$request->role_id){
            return response()->json(['status' => 'error']);
        }
        $role = Role::find($request->role_id);

        if($role->name == 'additional'){

            $userModel = User::find($request->user_id);
            $userModel->additionalRightsUsers()->sync([]);
            $userModel->additionalRightsDepartments()->sync([]);
        }

        $role->users()->detach($request->user_id);

        return response()->json(['status' => 'success']);
    }

    public function list(){
        $data = RoleAction::list();
        return response()->json(['status' => 'success', 'data' => $data]);
    }

    public function get(){
        return response()->json(['status' => 'success', 'data' => RoleAction::get()]);
    }



}
