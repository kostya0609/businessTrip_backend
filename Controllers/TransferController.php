<?php

namespace App\Modules\BusinessTrip\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\BusinessTrip\Action\TransferAction;

use App\Modules\BusinessTrip\Model\City;
use App\Modules\BusinessTrip\Model\CostUnit;
use App\Modules\BusinessTrip\Model\Target;

use Illuminate\Support\Facades\DB;

class TransferController extends Controller {

    public function cities(){
        $data = DB::table('bsi_wt_city')->get();

        DB::beginTransaction();
        try {
            $kol = 0;
            $total = count($data);
            foreach ($data as $city) {
                $newCityModel = new City();
                TransferAction::setCity($newCityModel, $city);
                $kol++;
            };
            echo 'transfer OK. Скопировано ' . strval($kol) . ' записей городов из '. strval($total);
            DB::commit();
        } catch (\Exception $e){
            DB::rollBack();
            echo $e->getMessage();
        }
        return;
    }

    public function targets(){
        $data = DB::table('bsi_wt_target')->get();

        DB::beginTransaction();
        try {
            $kol = 0;
            $total = count($data);
            foreach ($data as $target) {
                $newTargetModel = new Target();
                TransferAction::setTarget($newTargetModel, $target);
                $kol++;
            };
            echo 'transfer OK. Скопировано ' . strval($kol) . ' записей целей из '. strval($total);
            DB::commit();
        } catch (\Exception $e){
            DB::rollBack();
            echo $e->getMessage();
        }
        return;
    }

    public function costUnits(){
        $data = DB::table('bsi_wt_cost_unit')->get();

        DB::beginTransaction();
        try {
            $kol = 0;
            $total = count($data);
            foreach ($data as $costUnit) {
                $newCostUnitModel = new CostUnit();
                TransferAction::setCostUnit($newCostUnitModel, $costUnit);
                $kol++;
            };
            echo 'transfer OK. Скопировано ' . strval($kol) . ' записей целей из '. strval($total);
            DB::commit();
        } catch (\Exception $e){
            DB::rollBack();
            echo $e->getMessage();
        }
        return;
    }

}
