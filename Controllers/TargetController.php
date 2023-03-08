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
        return response()->json(['status' => 'success']);
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
        $targets = Target::where('active',1)->get();

        $targets = $targets->map(function($item){
            return ['value' => $item->id, 'label' => $item->name];
        });

        return response()->json(['status' => 'success', 'data' => $targets]);
    }
}
