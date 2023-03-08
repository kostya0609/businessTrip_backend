<?php
namespace App\Modules\BusinessTrip\Controllers;

use App\Http\Controllers\Controller;

use App\Modules\BusinessTrip\Model\CostList;
use App\Modules\BusinessTrip\Model\CostUnit;
use App\Modules\BusinessTrip\Model\Log;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;


class CostUnitController extends Controller {
    public function list(){

        $costUnit = CostUnit::get();
        $costUnit =$costUnit->map(function($item){
            return [
              'value'     => $item->id,
              'label'     => $item->name,
              'name_unit' => $item->name_unit,
            ];
        });

        return response()->json(['status' => 'success', 'data' => ['costUnit' => $costUnit]]);
    }

    public function edit(Request $request){
        $task_id = $request->task_id;
        $estimate = $request->estimate;
        if(!is_numeric($task_id) || count($estimate) == 0 || !$request->user_id)
            return response()->json(['status' => 'error', 'message' => 'Возникла ошибка']);

        DB::beginTransaction();
        try{
            CostList::where('task_id', '=', $task_id)->delete();

            foreach ($estimate as $item){
                $newEstimate = new CostList();
                $newEstimate->task_id         = $task_id;
                $newEstimate->cost_id         = $item['cost_id'];
                $newEstimate->unit_cost       = $item['cost'];
                $newEstimate->count_unit_cost = $item['count'];
                $newEstimate->save();
            }

            $log = new Log();
            $logMessage = 'Смета изменена';
            $log->setLog(
                $task_id,
                $request->user_id,
                $logMessage
            );

            DB::commit();
            return response()->json(['status' => 'success', 'message' => 'Успешно']);
        }catch (\Exception $e){
            DB::rollBack();
            return response()->json(['status' => 'error', 'message' => $e->getMessage()]);
        }


        return response()->json(['status' => 'success']);
    }



}
