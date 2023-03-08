<?php
namespace App\Modules\BusinessTrip\Action;

class Filter {
    public static function filter($objFilter, $modelFilter){

        $model = $modelFilter;
        foreach($objFilter as $key => $value){
            switch($value['type']){
                case 'number'       :
                {
                    if ($value['min'] && $value['max'] && $value['operation'] == '><')
                    {
                        $model = $model->where([[$key, '>=', $value['min']], [$key, '<=', $value['max']]]);
                    }
                    elseif ($value['min'] && $value['operation'] == '>')
                    {
                        $model = $model->where($key, '>=', $value['min']);
                    }
                    elseif ($value['min'] && $value['operation'] == '<')
                    {
                        $model = $model->where($key, '<=', $value['min']);
                    }
                    elseif ($value['min'] && $value['operation'] == '=')
                    {
                        $model = $model->where($key, '=', $value['min']);
                    }
                    break;
                }
                case 'date'         :
                {
                    if ($value['min'] && $value['max'] && $value['operation'] == '><')
                    {
                        $model = $model->where([[$key, '>=', date('Y-m-d H:i:s', strtotime($value['min'] . ' ' . '00:00:00'))],
                            [$key, '<=', date('Y-m-d H:i:s', strtotime($value['max'] . ' ' . '00:00:00'))]]);
                    }
                    elseif ($value['min'] && $value['operation'] == '>')
                    {
                        $model = $model->where($key, '>=', date('Y-m-d H:i:s', strtotime($value['min'] . ' ' . '00:00:00')));
                    }
                    elseif ($value['min'] && $value['operation'] == '<')
                    {
                        $model = $model->where($key, '<=', date('Y-m-d H:i:s', strtotime($value['min'] . ' ' . '00:00:00')));
                    }
                    elseif ($value['min'] && $value['operation'] == '=')
                    {
                        $model = $model->where([[$key, '>=', date('Y-m-d H:i:s', strtotime($value['min'] . ' ' . '00:00:00'))],
                            [$key, '<=', date('Y-m-d H:i:s', strtotime($value['min'] . ' ' . '23:59:59'))]]);
                    }
                    break;
                }
                case 'list' || 'searchList':
                {
                    //костыль
                    if($key == 'period_month')
                    {
                        $model = $model->where('period_type','=', 'month')->whereIn('period_value', $value['value']);
                    }
                    elseif($key == 'period_quarter')
                    {
                        $model = $model->where('period_type','=', 'quarter')->whereIn('period_value', $value['value']);
                    }
                    //конец костыля
                    else
                    {
                        $model = $model->whereIn($key, $value['value']);
                    }

                    break;
                }
            }
        }
        return $model;
    }
}
