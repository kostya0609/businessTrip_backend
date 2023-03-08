<?php

namespace App\Modules\BusinessTrip\Action;

class TransferAction {
    public static function setCity($model, $data){
        $model->id          = $data->ID;
        $model->name        = $data->NAME;
        $model->region      = $data->REGION;
        $model->population  = $data->POPULATION;
        $model->user_id     = $data->USER_ID;

        if($data->ACTIVE == 'Y') $model->active = 1; else $model->active = 0;
        $model->save();
    }

    public static function setTarget($model, $data){
        $model->id           = $data->ID;
        $model->name         = $data->NAME;
        $model->description  = $data->DESCRIPTION;

        if($data->ACTIVE == 'Y') $model->active = 1; else $model->active = 0;
        $model->save();
    }

    public static function setCostUnit($model, $data){
        $model->id              = $data->ID;
        $model->name            = $data->NAME;
        $model->name_unit       = $data->NAME_UNIT;
        $model->unit_price      = $data->UNIT_PRICE;
        $model->limit_cost_unit = $data->LIMIT_COST_UNIT;

        if($data->SUTOCHNIE == 'Y') $model->daily_allowance = 1; else $model->daily_allowance = 0;
        $model->save();
    }

}
