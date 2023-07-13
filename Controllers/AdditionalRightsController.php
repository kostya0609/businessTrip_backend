<?php

namespace App\Modules\BusinessTrip\Controllers;

use App\Modules\BusinessTrip\Model\Department;
use App\Modules\BusinessTrip\Model\Role;
use App\Modules\BusinessTrip\Model\User;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class AdditionalRightsController extends \Illuminate\Routing\Controller{

    public function setAdditionalRights(Request $request){
        if(!$request->user_id || !$request->client_id || !is_array($request->departments) || !is_array($request->individuals))
            return response()->json(['status' => 'error', 'message' => 'Возникла ошибка']);

        $user = User::find($request->client_id);

        $user->additionalRightsUsers()->sync($request->individuals);
        $user->additionalRightsDepartments()->sync($request->departments);

        return response()->json(['status' => 'success', 'message' => 'Права успешно добавлены']);
    }

    public function listAdditionalRights(Request $request){
        $role = Role::where('name', '=', 'additional')->first();
        $users = $role->users;
        $data = $users->map(function($item)
        {
            return [
                $item->full_name    => [
                    'users'         => $item->additionalRightsUsers->pluck('full_name'),
                    'departments'   => $item->additionalRightsDepartments->pluck('NAME'),
                ]
            ];
        });

        return response()->json(['status' => 'success', 'data' => $data]);
    }

}
