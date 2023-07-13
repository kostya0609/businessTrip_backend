<?php
namespace App\Modules\BusinessTrip\Controllers;

use App\Http\Controllers\Controller;

use App\Modules\BusinessTrip\Model\Log;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class LogController extends Controller {
    public function get(Request $request){

        if(!$request->task_id)
            return response()->json(['status' => 'error', 'message' => 'Возникла ошибка']);

        $logModels = Log::where('task_id','=',$request->task_id)->with('user')->orderBy('id', 'desc')->get();



        $data = $logModels->map(function($item){
            return [
                'date' => $item->date,
                'event' => $item->event,
                'user' =>  [
                    'link'=>"https://bitrix.bsi.local/company/personal/user/{$item->user->ID}/",
                    'photo'=> "https://bitrix.bsi.local/".$item->user->getPhoto()
                ]
            ];
        });

        return response()->json(['status' => 'success', 'data' => $data]);
    }


}
