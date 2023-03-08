<?php

namespace App\Modules\BusinessTrip\Action;

use App\Modules\BusinessTrip\Model\CostList;
use App\Modules\BusinessTrip\Model\CostUnit;

use App\Modules\BusinessTrip\Model\City;
use App\Modules\BusinessTrip\Model\Department;
use App\Modules\BusinessTrip\Model\User;
use App\Modules\BusinessTrip\Model\Company;

use App\Modules\BusinessTrip\Model\TaskDot;
use App\Modules\BusinessTrip\Model\TaskTarget;

class TaskAction {
    public static function setTask($model, $data){
        if($data['responsible_id'])                         $model->responsible_id = $data['responsible_id'];
        if($data['company_id'])                             $model->company_id = $data['company_id'];
        if($data['department_id'])                          $model->department_id = $data['department_id'];
        if($data['position'])                               $model->position = $data['position'];
        if($data['checking_account'])                       $model->checking_account = $data['checking_account'];
        if($data['comment'])                                $model->comment = $data['comment'];
        if($data['city_start_id'])                          $model->city_start_id = $data['city_start_id'];
        if($data['city_final_id'])                          $model->city_final_id = $data['city_final_id'];
        if($data['date_start'])                             $model->date_start = $data['date_start'];
        if($data['date_final'])                             $model->date_final = $data['date_final'];
        if($data['auto_travel'])                            $model->auto_travel = $data['auto_travel']; else $model->auto_travel = 0;
        if($data['auto_travel'] && $data['mark'])           $model->mark = $data['mark']; else $model->mark = null;
        if($data['auto_travel'] && $data['model'])          $model->model = $data['model']; else $model->model = null;
        if($data['auto_travel'] && $data['number'])         $model->number = $data['number']; else $model->number = null;
        if($data['auto_travel'] && $data['gasoline'])       $model->gasoline = $data['gasoline']; else $model->gasoline = null;
        if($data['auto_travel'] && $data['back_distance'])  $model->back_distance = $data['back_distance']; else $model->back_distance = null;
        if($data['document_link'])                          $model->document_link = $data['document_link'];
        $model->save();

        $task_id = $model->id;
        $dots    = $data['dots'];

        foreach ($dots as $dot){
            $newDot          = new TaskDot();
            $newDot->task_id = $task_id;

            if($dot->city_id)         $newDot->city_id   = $dot->city_id;
            if($dot->days)            $newDot->days      = $dot->days;
            if($dot->sort)            $newDot->sort      = $dot->sort;
            if($dot->distance)        $newDot->distance  = $dot->distance;

            $newDot->save();

            $dot_id  = $newDot->id;
            $targets = $dot->targets;

            foreach ($targets as $target){
                $newTarget          = new TaskTarget();
                $newTarget->task_id = $task_id;
                $newTarget->dot_id  = $dot_id;

                if($target->target_id)   $newTarget->target_id  = $target->target_id;
                if($target->comment)     $newTarget->comment    = $target->comment;
                if($target->contractor)  $newTarget->contractor = $target->contractor;
                if($target->sort)        $newTarget->sort       = $target->sort;

                $newTarget->save();
            }
        }
        return $model;
    }

    public static function listGrid($models){
        return $models
            ->get()
            ->map(function($item){

                $responsible = User::find($item->responsible_id)->full_name;
                $company     = Company::find($item->company_id)->NAME;
                $department  = Department::find($item->department_id)->NAME;

                $city_start  = City::find($item->city_start_id);
                $city_start  = $city_start->name . ' (' .$city_start->region . ')';

                $city_final  = City::find($item->city_final_id);
                $city_final  = $city_final->name . ' (' .$city_final->region . ')';

                $dots        = $item->dots->map(function($dot){
                    $city = $dot->city;
                    return [
                       'value' => $city->name . ' (' .$city->region . ')'
                    ];
                });

                return [
                    'id'                => [['value' => $item->id]],
                    'status'            => [['value' => Translate::translate($item->status)]],
                    'status_eng'        => [['value' => $item->status]],
                    'responsible'       => [['value' => $responsible]],
                    'company'           => [['value' => $company]],
                    'department'        => [['value' => $department]],
                    'position'          => [['value' => $item->position]],
                    'city_start'        => [['value' => $city_start]],
                    'city_final'        => [['value' => $city_final]],
                    'date_start'        => [['value' => $item->date_start->format('d.m.Y')]],
                    'date_final'        => [['value' => $item->date_final->format('d.m.Y')]],
                    'dots'              =>  $dots,
                    'date_created'      => [['value' => $item->created_at->format('d.m.Y')]],
                ];
            });
    }

    public static function detailTask($model){

        $auto_travel = $model->auto_travel;
        $limit_litr  = $model->date_start->format('m') < 10 && $model->date_final->format('m') > 3 ? 0.1 : 0.12;
        $gasoline    = $model->gasoline;

        $date_start  = $model->date_start;
        $date_final  = $model->date_final;
        $count_days  = (($date_final->getTimestamp() - $date_start->getTimestamp() ) / 86400 ) + 1;

        $cityModel   = City::find($model->city_start_id);
        $city_start  = $cityModel->name  . ' (' . $cityModel->region . ')';

        $cityModel   = City::find($model->city_start_id);
        $city_final  = $cityModel->name  . ' (' . $cityModel->region . ')';

        $task = [
            ['name' => 'ФИО пользователя',  'value' => User::find($model->responsible_id)->full_name],
            ['name' => 'Организация',       'value' => Company::find($model->company_id)->NAME],
            ['name' => 'Подразделение',     'value' => Department::find($model->department_id)->NAME],
            ['name' => 'Должность',         'value' => $model->position],
            ['name' => 'Расчетный счет',    'value' => $model->checking_account],
            ['name' => 'Комментарий',       'value' => $model->comment],
            ['name' => '',                  'value' => ''],

            ['name' => 'Город отъезда',     'value' => $city_start],
            ['name' => 'Дата отъезда',      'value' => $model->date_start->format('d.m.Y')],
            ['name' => 'Город возврата',    'value' => $city_final],
            ['name' => 'Дата возврата',     'value' => $model->date_final->format('d.m.Y')],
        ];

        if ($auto_travel){
            $agreement = null;
            $vicarious = null;
            foreach ($model->files as $file){
                if ($file->type == 'agreement' ){
                    $str = $file->id.',\''.$file->translated_name.'.' . $file->type_file . '\'';
                    $agreement = '<span class="businessTrip_vicarious"><a onclick="businessTripLoadFile('.$str.')" href="#'.$file->id.'">'.$file->original_name.'</a></span>';
                } else {
                    $str = $file->id.',\''.$file->translated_name.'.' . $file->type_file . '\'';
                    $vicarious = '<span class="businessTrip_vicarious"><a onclick="businessTripLoadFile('.$str.')" href="#'.$file->id.'">'.$file->original_name.'</a></span>';
                }
            }
            $auto_travel = [
                ['name' => '',                                                      'value' => ''],
                ['name' => 'Марка',                                                 'value' => $model->mark],
                ['name' => 'Модель',                                                'value' => $model->model],
                ['name' => 'Гос номер',                                             'value' => $model->number],
                ['name' => 'Стоимость бензина (1 литр)',                            'value' => $model->gasoline],
                ['name' => 'Соглашение об использовании автомобиля в личных целях', 'value' => $agreement],
                ['name' => 'Доверенность',                                          'value' => $vicarious],
            ];
            $task = array_merge($task, $auto_travel);
        };

        $dots         = $model->dots->sortBy('sort')->values()->all();
        $dots_to_send = [];
        $route_sheet  = [];
        $city_alive   = '';
        $total_GSM    = 0;

        foreach ($dots as $idx => $dot){
            if($auto_travel){
                if($idx == 0) $route = $city_start . ' - ' . $dot->city->name . ' (' . $dot->city->region . ')';
                else $route = $city_alive . ' - ' . $dot->city->name . ' (' . $dot->city->region . ')';

                $sum_1      = $limit_litr * $dot->distance * $gasoline;
                $sum_2      = $limit_litr * ($dot->days * $dot->city->limit_km) * $gasoline;
                $total_GSM  = $total_GSM + $sum_1 + $sum_2;

                $population = $dot->city->population / 1000;
                $pop        = 'до ' . round($population) . ' тыс. чел.';

                if ($population > 500) $pop = 'свыше ' . round($population) . ' тыс. чел.';

                $route_sheet[] = [
                    'number'    => count($route_sheet) + 1,
                    'route'     => $route,
                    'type'      => 'Трансфер',
                    'km_days'   => $dot->distance . ' км.',
                    'gasoline'  => $gasoline . ' руб.',
                    'sum'       => round($sum_1,2) . ' руб.'
                ];

                $route_sheet[] = [
                    'number'    => count($route_sheet) + 1,
                    'route'     => $dot->city->name . ' (' . $dot->city->region . ')',
                    'type'      => 'Движение по городу ' . $pop,
                    'km_days'   => $dot->days .' дн.',
                    'gasoline'  => $gasoline . ' руб.',
                    'sum'       => round($sum_2, 2) . ' руб.'
                ];

                if($idx == count($model->dots) - 1){
                    $sum_3      = $limit_litr * $model->back_distance * $gasoline;
                    $total_GSM  = $total_GSM + $sum_3;

                    $route_sheet[] = [
                        'number'    => count($route_sheet) + 1,
                        'route'     => $dot->city->name . ' (' . $dot->city->region . ')' . ' - ' . $city_final,
                        'type'      => 'Трансфер',
                        'km_days'   => $model->back_distance . ' км.',
                        'gasoline'  => $gasoline . ' руб.',
                        'sum'       => round($sum_3, 2) . ' руб.'
                    ];

                    $route_sheet[] = ['number'=> 'Всего', 'sum' => round($total_GSM, 2) . ' руб.'];
                }

                $city_alive = $dot->city->name . ' (' . $dot->city->region . ')';
            };

            $targets = $dot->targets->sortBy('sort')->values()->map(function($target, $idx){
                return [
                    'number'     => $idx + 1,
                    'target'     => $target->target->name,
                    'contractor' => $target->contractor,
                    'comment'    => $target->comment
                ];
            });

            $info = [ ['name' => 'Дней пребывания',  'value' => $dot->days] ];
            if($auto_travel) $info[] = ['name' => 'Км до города', 'value' => $dot->distance];

            $dots_to_send[] = [
                'city_name' => $dot->city->name . ' (' . $dot->city->region . ')',
                'info'      => $info,
                'targets'   => $targets,
            ];
        };

        $estimate = [];
        $total_estimate = 0;

        // cost_id = 5  это всегда id ГСМ
        if($auto_travel){
            $cost_list_GSM = CostList::where([ ['task_id' , '=', $model->id], ['cost_id' , '=', 5] ])->first();

            if ($cost_list_GSM){
                $cost_list_GSM->unit_cost = round($total_GSM , 2);
                $cost_list_GSM->save();
            } else {
                $newEstimate = new CostList();
                $newEstimate->task_id         = $model->id;
                $newEstimate->cost_id         = 5;
                $newEstimate->unit_cost       = round($total_GSM , 2);
                $newEstimate->count_unit_cost = 1;
                $newEstimate->save();
            }

        };
        $costLists = CostList::where('task_id', $model->id)->with('costUnit')->get();

        foreach ($costLists as $item){
            $sum = round($item->count_unit_cost * $item->unit_cost, 2);
            $total_estimate += $sum;
            $estimate[] = [
                'cost_id'   => $item->cost_id,
                'name'      => $item->costUnit->name,
                'name_unit' => $item->costUnit->name_unit,
                'count'     => $item->count_unit_cost,
                'cost'      => $item->unit_cost,
                'sum'       => $sum,
            ];
        }

        return [
            'status'            => $model->status,
            'auto_travel'       => $model->auto_travel,
            'task'              => $task,
            'dots'              => $dots_to_send,
            'route_sheet'       => $route_sheet,
            'estimate'          => $estimate,
            'total_estimate'    => round($total_estimate, 2)
        ];
    }
}

