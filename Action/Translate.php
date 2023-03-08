<?php
namespace App\Modules\BusinessTrip\Action;

use Illuminate\Support\Arr;

class Translate {
    const MAP = [
        'created'        => 'Новое задание',
        'approving'      => 'На согласовании',
        'fixing_problem' => 'На устранении замечаний',
        'signing'        => 'На подписании',
        'working'        => 'В работе',
        'archived'       => 'Архив',
        'canceled'       => 'Аннулирован',
        'completed'      => 'Выполнено',
    ];

    public static function translate($value):string{
        return self::MAP[$value];
    }

    public static function exists($value):string{
        $exists = Arr::exists(self::MAP, $value);
        return $exists;
    }
}
