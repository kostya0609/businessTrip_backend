<?php
namespace App\Modules\BusinessTrip\Controllers;

use App\Http\Controllers\Controller;

use App\Modules\BusinessTrip\Model\City;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class CityController extends Controller {
    public function list(Request $request){
        $sort   = $request->sort['name']  ? : 'id';
        $order  = $request->sort['order'] ? : 'desc';

        $limit  = is_integer($request->count) ? $request->count : 10;
        $offset = is_integer($request->page) ? $limit * ($request->page-1) : 0;
        $active = is_integer($request->active) ? $request->active : 1;

        $citiesModel = City::where('active', $active)->orderBy($sort, $order);

        $total       = $citiesModel->count();

        $citiesModel = $citiesModel->offset($offset)->limit($limit)->get();

        return response()->json(['status' => 'success', 'data' => ['cities' => $citiesModel, 'total' => $total]]);
    }

    public function add(Request $request){
        return response()->json(['status' => 'success']);
    }

    public function active(Request $request){
        if(!is_integer($request->city_id) && !is_integer($request->user_id) && !is_integer($request->active))
            return response()->json(['status' => 'error', 'message' => 'Возникла ошибка']);

        DB::beginTransaction();
        try{
            $cityModel = City::find($request->city_id);
            $cityModel->active = $request->active;
            $cityModel->save();
            DB::commit();
            return response()->json(['status' => 'success']);
        }catch (\Exception $e){
            DB::rollBack();
            return response()->json(['status' => 'error', 'message' => $e->getMessage()]);
        }
    }

}
