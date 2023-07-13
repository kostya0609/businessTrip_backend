<?php
namespace App\Modules\BusinessTrip\Controllers;

use App\Http\Controllers\Controller;

use App\Modules\BusinessTrip\Model\CostList;
use App\Modules\BusinessTrip\Model\CostUnit;
use App\Modules\BusinessTrip\Model\Log;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;


class CostUnitController extends Controller {
    public function list(Request $request){
        if ($request->sort){
            $sort   = $request->sort['name']  ?: 'id';
            $order  = $request->sort['order'] ?: 'asc';
        }

        $limit  = is_integer($request->count) ? $request->count : 10;
        $offset = is_integer($request->page) ? $limit * ($request->page-1) : 0;
        $total    = 0;

        if ($request->all_unit) {
            $costUnit = CostUnit::get();
        }else{
            $costUnit = CostUnit::orderBy($sort, $order);
            $total    = $costUnit->count();
            $costUnit = $costUnit->offset($offset)->limit($limit)->get();
        }

        $costUnit = $costUnit->map(function($item){
            return [
              'value'           => $item->id,
              'label'           => $item->name,
              'name_unit'       => $item->name_unit,
              'unit_price'      => $item->unit_price,
              'limit_cost_unit' => $item->limit_cost_unit,
              'daily_allowance' => $item->daily_allowance,
            ];
        });


        return response()->json(['status' => 'success', 'data' => ['costUnit' => $costUnit, 'total' => $total] ]);
    }

    public function get(Request $request){
        $cost_unit_id = $request->cost_unit_id;

        if(!$cost_unit_id)
            return response()->json(['status' => 'error', 'message' => 'Возникла ошибка']);

        $costUnitModel = CostUnit::find($cost_unit_id);
        return response()->json(['status' => 'success', 'data' => $costUnitModel]);
    }

    public function add(Request $request){
        if(!$request->name || !$request->name_unit)
            return response()->json(['status' => 'error', 'message' => 'Возникла ошибка']);

        DB::beginTransaction();
        try {
            $newCostUnit = new CostUnit();
            if($request->name)            $newCostUnit->name            = $request->name;
            if($request->name_unit)       $newCostUnit->name_unit       = $request->name_unit;
            if($request->unit_price)      $newCostUnit->unit_price      = $request->unit_price;      else $newCostUnit->unit_price = 0;
            if($request->limit_cost_unit) $newCostUnit->limit_cost_unit = $request->limit_cost_unit; else $newCostUnit->limit_cost_unit = 0;
            if($request->daily_allowance) $newCostUnit->daily_allowance = $request->daily_allowance; else $newCostUnit->daily_allowance = 0;
            $newCostUnit->save();

            $cost_unit_id = $newCostUnit->id;

            DB::commit();
            return response()->json(['status' => 'success', 'message' => 'Успешно', 'cost_unit_id'=> $cost_unit_id]);

        } catch (\Exception $e){
            DB::rollBack();
            return response()->json(['status' => 'error', 'message' => $e->getMessage()]);
        }
    }

    public function edit(Request $request){
        if(!$request->name || !$request->name_unit)
            return response()->json(['status' => 'error', 'message' => 'Возникла ошибка']);
        $cost_unit_id = $request->id;

        DB::beginTransaction();
        try {
            $editCostUnit = CostUnit::find($cost_unit_id);
            if($request->name)            $editCostUnit->name            = $request->name;
            if($request->name_unit)       $editCostUnit->name_unit       = $request->name_unit;
            if($request->unit_price)      $editCostUnit->unit_price      = $request->unit_price;      else $editCostUnit->unit_price = 0;
            if($request->limit_cost_unit) $editCostUnit->limit_cost_unit = $request->limit_cost_unit; else $editCostUnit->limit_cost_unit = 0;
            if($request->daily_allowance) $editCostUnit->daily_allowance = $request->daily_allowance; else $editCostUnit->daily_allowance = 0;
            $editCostUnit->save();

            DB::commit();
            return response()->json(['status' => 'success', 'message' => 'Успешно', 'cost_unit_id'=> $cost_unit_id]);

        } catch (\Exception $e){
            DB::rollBack();
            return response()->json(['status' => 'error', 'message' => $e->getMessage()]);
        }
    }

    public function delete(Request $request){
        $cost_unit_id = $request->cost_unit_id;
        if(!$cost_unit_id)
            return response()->json(['status' => 'error', 'message' => 'Возникла ошибка']);

        DB::beginTransaction();
        try {
            CostUnit::find($cost_unit_id)->delete();
            DB::commit();
            return response()->json(['status' => 'success', 'message' => 'Успешно']);
        }catch (\Exception $e){
            DB::rollBack();
            return response()->json(['status' => 'error', 'message' => $e->getMessage()]);
        }
    }

    public function estimateEdit(Request $request){
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
