<?php

namespace App\Modules\BusinessTrip\Controllers;
use Illuminate\Http\Request;
use App\Modules\BusinessTrip\Model\NeedAction;

class NeedActionController{

    public function update(Request $request){
        $usersNeedActions = NeedAction::where('task_id', $request->task_id)->get()->toArray();

        foreach ($usersNeedActions as $key => $userAction){

            $key = array_search($userAction['user_id'], array_column( $request->users_id, 'user'));

            if(!$key)
                NeedAction::where([['user_id','=',$userAction['user_id']],['task_id', '=', $userAction['task_id']]])->delete();
        }

        foreach ($request->users_id as $key => $user){

            $res = NeedAction::where([['user_id','=', $user['user']], ['task_id', '=', $request->task_id]])->get();

            if(!$res->count()){
                NeedAction::insert([ 'user_id' => $user['user'], 'task_id' => $request->task_id]);
            }
        }

        return response()->json(['status' => 'success']);
    }

    public function badge(Request $request){

        $res = NeedAction::where('user_id', $request->user_id)->get();
        $res = $res->count() ?: '';

        return response()->json(['status' => 'success', 'data' => ['count' => $res] ]);
    }

}
