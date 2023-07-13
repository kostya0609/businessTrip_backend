<?php
namespace App\Modules\BusinessTrip\Controllers;

use App\Http\Controllers\Controller;

use App\Modules\BusinessTrip\Model\Target;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class TargetController extends Controller {
    public function list(Request $request){
        $sort   = $request->sort['name']  ?: 'id';
        $order  = $request->sort['order'] ?: 'asc';

        $limit  = is_integer($request->count) ? $request->count : 10;
        $offset = is_integer($request->page) ? $limit * ($request->page-1) : 0;
        $active = is_integer($request->active) ? $request->active : 1;

        $targetsModel = Target::where('active', $active)->orderBy($sort, $order);
        $total        = $targetsModel->count();

        $targetsModel = $targetsModel->offset($offset)->limit($limit)->get();

        return response()->json(['status' => 'success', 'data' => ['targets' => $targetsModel, 'total' => $total]]);
    }

    public function add(Request $request){
        if(!$request->name || !$request->description || !is_integer($request->active))
            return response()->json(['status' => 'error', 'message' => 'Возникла ошибка']);

        DB::beginTransaction();
        try {
            $newTarget = new Target();
            if($request->name)        $newTarget->name        = $request->name;
            if($request->description) $newTarget->description = $request->description;
            if($request->active)      $newTarget->active      = $request->active; else $newTarget->active = 0;
            if($request->user_id)     $newTarget->user_id     = $request->user_id;

            $newTarget->save();
            $target_id = $newTarget->id;

            DB::commit();
            return response()->json(['status' => 'success', 'message' => 'Успешно', '$target_id'=> $target_id]);

        } catch (\Exception $e){
            DB::rollBack();
            return response()->json(['status' => 'error', 'message' => $e->getMessage()]);
        }
    }

    public function edit(Request $request){
        if(!$request->id || !$request->name || !$request->description || !is_integer($request->active))
            return response()->json(['status' => 'error', 'message' => 'Возникла ошибка']);
        $target_id = $request->id;

        DB::beginTransaction();
        try {
            $editTraget = Target::find($target_id);
            if($request->name)        $editTraget->name        = $request->name;
            if($request->description) $editTraget->description = $request->description;
            if($request->active)      $editTraget->active      = $request->active; else $editTraget->active = 0;
            if($request->user_id)     $editTraget->user_id     = $request->user_id;

            $editTraget->save();

            DB::commit();
            return response()->json(['status' => 'success', 'message' => 'Успешно', 'target_id'=> $target_id]);

        } catch (\Exception $e){
            DB::rollBack();
            return response()->json(['status' => 'error', 'message' => $e->getMessage()]);
        }
    }

    public function active(Request $request){
        if(!is_integer($request->target_id) && !is_integer($request->user_id) && !is_integer($request->active))
            return response()->json(['status' => 'error', 'message' => 'Возникла ошибка']);

        DB::beginTransaction();
        try{
            $targetModel = Target::find($request->target_id);
            $targetModel->active = $request->active;
            $targetModel->save();
            DB::commit();
            return response()->json(['status' => 'success']);
        }catch (\Exception $e){
            DB::rollBack();
            return response()->json(['status' => 'error', 'message' => $e->getMessage()]);
        }
    }

    public function get(Request $request){

        if($request->all){
            $targets = Target::where('active',1)->get();

            $targets = $targets->map(function($item){
                return ['value' => $item->id, 'label' => $item->name];
            });
            return response()->json(['status' => 'success', 'data' => $targets]);

        }else{
            $target_id = $request->target_id;

            if(!$target_id)
                return response()->json(['status' => 'error', 'message' => 'Возникла ошибка']);

            $targetModel = Target::find($target_id);
            return response()->json(['status' => 'success', 'data' => $targetModel]);
        }
    }
}
