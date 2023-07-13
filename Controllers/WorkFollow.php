<?php
namespace App\Modules\BusinessTrip\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\BusinessTrip\Model\City;
use App\Modules\BusinessTrip\Model\Log;
use App\Modules\BusinessTrip\Model\Task;

use App\Modules\BusinessTrip\Model\User;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;

class WorkFollow extends Controller {
    public function set(Request $request){
        $url = 'https://bitrix.bsi.local/local/rest/wt/';
        //$url = 'https://test.bitrix.bsi.local/local/rest/wt/';

        if(!$request->task_id || !is_numeric($request->task_id) )
            return response()->json(['status' => 'error', 'message' => 'Возникла ошибка']);

        $task_id = $request->task_id;

        $taskModel = Task::with(['dots.city'])->find($task_id);

        if($taskModel->document_link)
            return response()->json(['status' => 'success', 'message' => 'Успешно']);

        $USER_ID        = $taskModel->responsible_id;
        $DATE_START     = $taskModel->date_start->format('d.m.Y');
        $DATE_FINAL     = $taskModel->date_final->format('d.m.Y');
        $USER_FULL_NAME =  User::find($taskModel->responsible_id)->full_name;
        $POSITION_NAME  = $taskModel->position;
        $ACCOUNTANT_ID  = $taskModel->accountant_id;
        $OVER_BUDGET    = $taskModel->over_budget;
        $dtStr          = Carbon::parse($taskModel->date_final)->addDays(3)->format('d.m.Y');

        $cityModel   = City::find($taskModel->city_start_id);
        $city_start  = $cityModel->name  . ' (' . $cityModel->region . ')';

        $cityModel   = City::find($taskModel->city_start_id);
        $city_final  = $cityModel->name  . ' (' . $cityModel->region . ')';

        $dots = $taskModel->dots;

        $ROUTE_LIST = 'г. ' . $city_start;

        foreach ($dots as $dot ){
            $ROUTE_LIST = $ROUTE_LIST . ' -  г. ' . $dot['city']['name'] . ' (' . $dot['city']['region'] . ')';
        }
        $ROUTE_LIST = $ROUTE_LIST . ' -  г. ' . $city_final . '.';

        $TASK_DATA = [
            'TASK_ID'        => $task_id,
            'USER_ID'        => $USER_ID,
            'OVER_BUDGET'    => $OVER_BUDGET,
            'ROUTE_LIST'     => $ROUTE_LIST,
            'DATE_START'     => $DATE_START,
            'DATE_FINAL'     => $DATE_FINAL,
            'USER_FULL_NAME' => $USER_FULL_NAME,
            'POSITION_NAME'  => $POSITION_NAME,
            'dtStr'          => $dtStr,
            'ACCOUNTANT_ID'  => $ACCOUNTANT_ID,
        ];

        try {
            $set_work_follow = Http::withOptions(['verify' => false])->withHeaders([
                'Authorization' => 'Basic cmVzdDpSRVNUcmVzdCEhIQ==',
            ])->post($url, $TASK_DATA);

            $set_work_follow = json_decode($set_work_follow->body())->data;

            $taskModel->document_link = json_encode($set_work_follow);
            $taskModel->save();

            $log = new Log();
            $logMessage = 'В СЭД созданы поручения по командировочному заданию';
            $log->setLog(
                $task_id,
                $taskModel->responsible_id,
                $logMessage
            );

            return response()->json(['status' => 'success', 'message' => 'Успешно', 'data'=> $set_work_follow]);

        } catch (\Exception $e){
            return response()->json(['status' => 'error', 'message' => $e->getMessage()]);
        }
    }

    public function get(Request $request){
        if(!$request->task_id || !is_numeric($request->task_id) )
            return response()->json(['status' => 'error', 'message' => 'Возникла ошибка']);
        $task_id = $request->task_id;

        try {
            $taskModel = Task::find($task_id);
            $data_link_doc = json_decode($taskModel->document_link);
            return response()->json(['status' => 'success', 'message' => 'Успешно', 'data'=> $data_link_doc]);

        } catch (\Exception $e){
            return response()->json(['status' => 'error', 'message' => $e->getMessage()]);
        }
    }

}
