<?php
namespace App\Modules\BusinessTrip\Controllers;

use App\Http\Controllers\Controller;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

use App\Modules\BusinessTrip\Model\Company;
use App\Modules\BusinessTrip\Model\Department;
use App\Modules\BusinessTrip\Model\User;
use App\Modules\BusinessTrip\Model\City;
use App\Modules\BusinessTrip\Model\Target;

class SearchController extends Controller {

    public function trimStr($str){
        return trim($str);
    }

    public function city(Request $request){
        $str = $this->trimStr($request->q);

        $result = City::where('active',1)
            ->where('name','like','%'.$str.'%')
            ->limit(10)
            ->get();

        $cities = [];
        foreach ($result as $el){
            $cities[] = ['value' => $el->id, 'label' => trim($el->name . ' (' . $el->region . ')')];
        }
        return response()->json(['status' => 'success', 'data' => $cities]);
    }

    public function target(Request $request){
        $str = $this->trimStr($request->q);

        $result = Target::where('active',1)
            ->where('name','like','%'.$str.'%')
            ->limit(10)
            ->get();

        $targets = [];
        foreach ($result as $el){
            $targets[] = ['value' => $el->id, 'label' => trim($el->name)];
        }
        return response()->json(['status' => 'success', 'data' => $targets]);
    }

    public function user(Request $request){
        $to_str = explode(' ',$this->trimStr($request->q));
        $result = User::where('ACTIVE','Y')->where(function ($query) use ($to_str){
            foreach ($to_str as $word){
                if(!empty($word)){
                    $query->where(DB::raw('CONCAT_WS(LAST_NAME, " ", NAME, " ", SECOND_NAME)'),'like','%'.$word.'%');
                }
            }
        })
            ->limit(10)
            ->get();
        $data = [];
        foreach ($result as $el){
            if($el->ACTIVE == 'Y'){
                $data[] = ['value' => $el->ID, 'label' => $el->full_name];
            } else {
                $data[] = ['value' => $el->ID, 'label' => $el->full_name . ' (Уволен)'];
              }
        }
        return response()->json(['status' => 'success', 'data' => $data]);
    }

    public function company(Request $request){
        $str = $this->trimStr($request->q);

        $result = Company::where('ACTIVE','Y')
            ->where('IBLOCK_ID',38)
            ->where('NAME','like','%'.$str.'%')
            ->limit(10)
            ->get();

        $company = [];
        foreach ($result as $el){
            $company[] = ['value' => $el->ID, 'label' => trim($el->NAME)];
        }
        return response()->json(['status' => 'success', 'data' => $company]);
    }

    public function department(Request $request){
        $to_str = explode(' ',$this->trimStr($request->q));
        $result = Department::where('IBLOCK_ID','=' ,5)
            ->where(function($query) use ($to_str){
                foreach($to_str as $word){
                    if(!empty($word)){
                        $query->where('NAME', 'like', '%'.$word.'%');
                    }
                }
            })
            ->limit(10)
            ->get();

        $data = $result->map(function($el){
            return [
                'value' => $el->ID,
                'label' => $el->NAME
            ];
        });
        return response()->json(['status' => 'success', 'data' => $data]);
    }

}
