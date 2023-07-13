<?php
namespace App\Modules\BusinessTrip\Controllers;

use App\Http\Controllers\Controller;

use App\Modules\BusinessTrip\Model\City;
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

    public function get(Request $request){
        $city_id = $request->city_id;

        if(!$city_id)
            return response()->json(['status' => 'error', 'message' => 'Возникла ошибка']);

        $cityModel = City::find($city_id);
        return response()->json(['status' => 'success', 'data' => $cityModel]);
    }

    public function edit(Request $request){
        if(!$request->id || !$request->name || !$request->region || !is_integer((int)$request->population) || !is_integer($request->active))
            return response()->json(['status' => 'error', 'message' => 'Возникла ошибка']);
        $city_id = $request->id;


        DB::beginTransaction();
        try {
            $editCity = City::find($city_id);
            if($request->name)       $editCity->name       = $request->name;
            if($request->region)     $editCity->region     = $request->region;
            if($request->population) $editCity->population = $request->population;
            if($request->active)     $editCity->active     = $request->active; else $editCity->active = 0;
            if($request->user_id)    $editCity->user_id    = $request->user_id;

            $editCity->save();

            DB::commit();
            return response()->json(['status' => 'success', 'message' => 'Успешно', 'city_id'=> $city_id]);

        } catch (\Exception $e){
            DB::rollBack();
            return response()->json(['status' => 'error', 'message' => $e->getMessage()]);
        }
    }

    public function add(Request $request){

        if(!$request->name || !$request->region || !is_integer((int)$request->population) || !is_integer($request->active))
            return response()->json(['status' => 'error', 'message' => 'Возникла ошибка']);

        DB::beginTransaction();
        try {
            $newCity = new City();
            if($request->name)       $newCity->name       = $request->name;
            if($request->region)     $newCity->region     = $request->region;
            if($request->population) $newCity->population = $request->population;
            if($request->active)     $newCity->active     = $request->active; else $newCity->active = 0;
            if($request->user_id)    $newCity->user_id    = $request->user_id;

            $newCity->save();
            $city_id = $newCity->id;

            DB::commit();
            return response()->json(['status' => 'success', 'message' => 'Успешно', 'city_id'=> $city_id]);

        } catch (\Exception $e){
            DB::rollBack();
            return response()->json(['status' => 'error', 'message' => $e->getMessage()]);
        }
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
