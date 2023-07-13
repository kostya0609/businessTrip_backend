<?php
namespace App\Modules\BusinessTrip\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\BusinessTrip\Model\City;
use App\Modules\BusinessTrip\Model\Company;
use App\Modules\BusinessTrip\Model\CostList;
use App\Modules\BusinessTrip\Model\Department;
use App\Modules\BusinessTrip\Model\Task;
use App\Modules\BusinessTrip\Model\User;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;

class DocumentController extends Controller {

    private function getTableAsText($table, $obPHPWord){
        $objWriter = \PhpOffice\PhpWord\IOFactory::createWriter($obPHPWord, 'Word2007');

        $partDocument = $objWriter->getWriterPart('document');
        $parentWriter = $partDocument->getParentWriter();
        $useDiskCaching = false;
        if (!is_null($parentWriter)) {
            if ($parentWriter->isUseDiskCaching()) {
                $useDiskCaching = true;
            }
        }
        // Get XMLWriter
        if ($useDiskCaching) {
            $xmlWriter = new \PhpOffice\PhpWord\Shared\XMLWriter(\PhpOffice\PhpWord\Shared\XMLWriter::STORAGE_DISK, $parentWriter->getDiskCachingDirectory(), \PhpOffice\PhpWord\Settings::hasCompatibility());
        }
        else {
            $xmlWriter = new \PhpOffice\PhpWord\Shared\XMLWriter(\PhpOffice\PhpWord\Shared\XMLWriter::STORAGE_MEMORY, './', \PhpOffice\PhpWord\Settings::hasCompatibility());
        }
        // Get Table Element
        $writer = new \PhpOffice\PhpWord\Writer\Word2007\Element\Table($xmlWriter, $table);
        // Write to xml
        $writer->write();
        return $xmlWriter->getData();
    }

    public function get(Request $request){
        if(!$request->task_id)
            return response()->json(['status' => 'error', 'message' => 'Возникла ошибка, нет id задания']);

         if(!$request->type_file)
             return response()->json(['status' => 'error', 'message' => 'Возникла ошибка, нет типа файла']);

        $task_id         = $request->task_id;
        $type_file       = $request->type_file;

        $taskModel       = Task::with(['dots.targets.target', 'dots.city'])->find($task_id);

        $auto_travel     = $taskModel->auto_travel;
        $limit_litr      = $taskModel->date_start->format('m') < 10 && $taskModel->date_final->format('m') > 3 ? 0.1 : 0.12;
        $gasoline        = $taskModel->gasoline;

        $company         = Company::find($taskModel->company_id)->NAME;
        $FIO             = User::find($taskModel->responsible_id)->full_name;
        $position        = $taskModel->position;
        $department      = Department::find($taskModel->department_id)->NAME;

        $date_start      = $taskModel->date_start;
        $date_final      = $taskModel->date_final;
        $count_days      = (($date_final->getTimestamp() - $date_start->getTimestamp() ) / 86400 ) + 1;

        $cityModel       = City::find($taskModel->city_start_id);
        $city_start      = ['name' => $cityModel->name, 'region' => $cityModel->region];

        $cityModel       = City::find($taskModel->city_start_id);
        $city_final      = ['name' => $cityModel->name, 'region' => $cityModel->region];

        $dots            = $taskModel->dots->sortBy('sort')->values()->all();

        $transit_dots    = [];
        $route_sheet     = [];
        $city_alive      = '';
        $city_alive_days = '';
        $total_GSM       = 0;
        $total_distance  = 0;
        $city_date       = '';

        $count_city_days = 0;

        foreach ($dots as $idx => $dot){
            if($auto_travel){
                if($idx == 0) {
                    $route     = $city_start['name'] . ' ( ' . $city_start[ 'region'] . ' )' . ' - ' . $dot->city->name . ' (' . $dot->city->region . ')';
                    $city_date = $date_start;
                }else{
                    $route     = $city_alive . ' - ' . $dot->city->name . ' (' . $dot->city->region . ')';
                    $city_date = Carbon::parse($city_date)->addDays($city_alive_days);
                }

                $sum_1           = $limit_litr * $dot->distance * $gasoline;
                $sum_2           = $limit_litr * ($dot->days * $dot->city->limit_km) * $gasoline;

                $count_city_days = $count_city_days + $dot->days;

                $total_GSM       = $total_GSM + $sum_1 + $sum_2;
                $total_distance  = $total_distance + $dot->distance + ($dot->city->limit_km  *  $dot->days);

                $population = $dot->city->population / 1000;
                $pop        = 'до ' . round($population) . ' тыс. чел.';

                if ($population > 500) $pop = 'свыше ' . round($population) . ' тыс. чел.';

                $route_sheet[] = [
                    'route'          => $route . ' - трансфер',
                    'population'     => '',
                    'date'           => '',
                    'km'             => '',
                    'days'           => '',
                    'distance'       => $dot->distance,
                    'total_distance' => $dot->distance,
                    'sum'            => round($sum_1,2),
                ];

                $route_sheet[] = [
                    'route'          => $dot->city->name . ' (' . $dot->city->region . ')' . ' - движение по городу',
                    'population'     => $pop,
                    'date'           => $city_date->format('d.m.Y'),
                    'km'             => $dot->city->limit_km,
                    'days'           => $dot->days,
                    'distance'       => '',
                    'total_distance' => ($dot->city->limit_km  *  $dot->days),
                    'sum'            => round($sum_2, 2)
                ];

                if($idx == count($taskModel->dots) - 1){
                    $sum_3          = $limit_litr * $taskModel->back_distance * $gasoline;
                    $total_GSM      = $total_GSM + $sum_3;
                    $total_distance = $total_distance + $taskModel->back_distance;

                    $route_sheet[] = [
                        'route'          => $dot->city->name . ' (' . $dot->city->region . ')' . ' - ' . $city_final['name'] . ' ( ' . $city_final['region'] . ' )' . ' - трансфер',
                        'population'     => '',
                        'date'           => '',
                        'km'             => '',
                        'days'           => '',
                        'distance'       => $taskModel->back_distance,
                        'total_distance' => $taskModel->back_distance,
                        'sum'            => round($sum_3, 2)
                    ];

                    $route_sheet[] = [
                        'route'            => 'Итого',
                        'type'             => '',
                        'population'       => '',
                        'date'             => '',
                        'km'               => '',
                        'days'             => $count_city_days,
                        'distance'         => '',
                        'total_distance'   => $total_distance,
                        'sum'              => round($total_GSM, 2)
                    ];
                }
                $city_alive      = $dot->city->name . ' (' . $dot->city->region . ')';
                $city_alive_days = $dot->days;
            };

            $targets = $dot->targets->sortBy('sort')->values()->map(function($target, $idx){
                return [
                    'number'     => $idx + 1,
                    'target'     => $target->target->name,
                    'contractor' => $target->contractor,
                    'comment'    => $target->comment
                ];
            });

            $transit_dots[] = [
                'name'    => $dot->city->name,
                'region'  => $dot->city->region,
                'targets' => $targets,
            ];
        };

        //ниже создаем файл с отчетом по командировки
        if ($type_file == 'otchet'){
            $file_name     = 'otchet';
            $route         = $city_start['name'] . ', ';
            $targets = []; // для отчета

            foreach ($transit_dots as $dot){
                $route = $route . $dot['name'] . ', ';
                foreach ($dot['targets'] as $target){
                    $targets[] =  $target['target'];
                }
            };

            $route = $route . $city_final['name'];

            $document = new \PhpOffice\PhpWord\TemplateProcessor(app_path() . '/Modules/BusinessTrip/Doc_templates/'.$file_name.'.docx');
            $document->setValue('company',$company);
            $document->setValue('marshrut',$route);
            $document->setValue('department',$department);
            $document->setValue('fio',$FIO);
            $document->setValue('date_start',$date_start->format('d.m.Y'));
            $document->setValue('date_final',$date_final->format('d.m.Y'));
            $document->setValue('target_unque', implode("<w:br/>", array_unique($targets)));

            $obPHPWord = new \PhpOffice\PhpWord\PhpWord();
            $section   = $obPHPWord->addSection();
            //$section   = $obPHPWord->createSection();
            $table     = $section->addTable([
                'borderSize' => 2,
                'borderColor' => '000000',
                'alignment'=> \PhpOffice\PhpWord\SimpleType\JcTable::CENTER,
                'layout'=> \PhpOffice\PhpWord\Style\Table::LAYOUT_FIXED
            ]);

            $table->addRow(800);
            $table->addCell(530, ['valign' => 'center'])->addText(
                '№<w:br/>п/п<w:br/>',
                ['size' => 9,'bold' => true,'name' => 'Times New Roman'],
                ['hanging' => 0,'alignment'=>\PhpOffice\PhpWord\SimpleType\JcTable::CENTER]
            );
            $table->addCell(3700, ['valign' => 'center'])->addText(
                'Контрагент',
                ['size' => 9,'bold' => true,'name' => 'Times New Roman'],
                ['hanging' => 0,'alignment'=>\PhpOffice\PhpWord\SimpleType\JcTable::CENTER]
            );
            $table->addCell(3700, ['valign' => 'center'])->addText(
                'Цель',
                ['size' => 9,'bold' => true,'name' => 'Times New Roman'],
                ['hanging' => 0,'alignment'=>\PhpOffice\PhpWord\SimpleType\JcTable::CENTER]
            );
            $table->addCell(3700, ['valign' => 'center'])->addText(
                'Комментарий',
                ['size' => 9,'bold' => true,'name' => 'Times New Roman'],
                ['hanging' => 0,'alignment'=>\PhpOffice\PhpWord\SimpleType\JcTable::CENTER]
            );
            $table->addCell(3700, ['valign' => 'center'])->addText(
                'Краткий отчет о выполнении задания',
                ['size' => 9,'bold' => true,'name' => 'Times New Roman'],
                ['hanging' => 0,'alignment'=>\PhpOffice\PhpWord\SimpleType\JcTable::CENTER]
            );
            $cntDot = 1;
            foreach ($transit_dots as $dot){
                $table->addRow();
                $table->addCell(530, ['valign' => 'center'])->addText(
                    $cntDot,
                    ['size' => 9,'bold' => true,'name' => 'Times New Roman'],
                    ['hanging' => 0,'alignment'=>\PhpOffice\PhpWord\SimpleType\JcTable::CENTER]
                );
                $table->addCell(14800, ['gridSpan' => 4,'bold' => true,'valign' => 'center'])->addText(
                    $dot['name'] . ' ( ' . $dot['region'] . ' ) ',
                    ['size' => 9,'bold' => true,'name' => 'Times New Roman'],
                    ['hanging' => 0,'alignment'=>\PhpOffice\PhpWord\SimpleType\JcTable::START]
                );
                foreach ($dot['targets'] as $target){
                    $table->addRow();
                    $table->addCell(530, ['valign' => 'center'])->addText(
                        $cntDot.'.'.$target['number'],
                        ['size' => 9,'name' => 'Times New Roman'],
                        ['hanging' => 0,'alignment'=>\PhpOffice\PhpWord\SimpleType\JcTable::START]
                    );
                    $table->addCell(3700, ['valign' => 'center'])->addText(
                        $target['contractor'],
                        ['size' => 9,'name' => 'Times New Roman'],
                        ['hanging' => 0,'alignment'=>\PhpOffice\PhpWord\SimpleType\JcTable::START]
                    );
                    $table->addCell(3700, ['valign' => 'center'])->addText(
                        $target['target'],
                        ['size' => 9,'name' => 'Times New Roman'],
                        ['hanging' => 0,'alignment'=>\PhpOffice\PhpWord\SimpleType\JcTable::CENTER]
                    );
                    $table->addCell(3700, ['valign' => 'center'])->addText(
                        $target['comment'],
                        ['size' => 9,'name' => 'Times New Roman'],
                        ['hanging' => 0,'alignment'=>\PhpOffice\PhpWord\SimpleType\JcTable::CENTER]
                    );
                    $table->addCell(3700, ['valign' => 'center']);
                }
                $cntDot++;
            }

            $sTableText = $this->getTableAsText($table, $obPHPWord);
            $document->setValue('table', $sTableText);
            $document->saveAs(app_path() . '/Modules/BusinessTrip/tmp/' . $file_name . '_' . $task_id . '.docx');

            $path = app_path() . '/Modules/BusinessTrip/tmp/' . $file_name . '_' . $task_id . '.docx';
            return response()->file($path);

        }

         //ниже создаем файл со сметой
        if ($type_file == 'smeta'){
            $route = '';
            foreach ($transit_dots as $dot){
                $route ?
                    $route = $route. ', ' . $dot['name'] . ' ( ' . $dot['region'] . ' )' :
                    $route = $dot['name'] . ' ( ' . $dot['region'] . ' )';
            };

            $file_name        = 'smeta';
            $costLists        = CostList::where('task_id', $taskModel->id)->with('costUnit')->get();
            $estimate         = [];
            $total_estimate   = 0;
            $bank_info = [
                'rs'   => $taskModel->checking_account,
                'bank' => 'нет данных с 1С',
                'addr' => 'нет данных с 1С',
                'ks'   => 'нет данных с 1С',
                'bik'  => 'нет данных с 1С',
            ];

            $responsible_id = User::find($taskModel->responsible_id);

            $response = Http::withBasicAuth('bitrix','78523')
                ->post('http://c1-it-s-1c/ZUP/hs/crm/API1/PersonalAccounts', [
                    'FIZ_LICO'      => $responsible_id->XML_ID,
                    'accountNumber' => $bank_info['rs']
                ]);
            $answer = $response->json();

            if(!$answer['error']){
                $bank_info['bank'] = $answer['result']['bank_name'];
                $bank_info['addr'] = $answer['result']['addres'];
                $bank_info['ks']   = $answer['result']['correspondent_account'];
                $bank_info['bik']  = $answer['result']['bik'];
            };

            foreach ($costLists as $item){
                $sum             = round($item->count_unit_cost * $item->unit_cost, 2);
                $total_estimate  += $sum;
                $estimate[]      = [
                    'name'      => $item->costUnit->name,
                    'name_unit' => $item->costUnit->name_unit,
                    'count'     => $item->count_unit_cost,
                    'cost'      => $item->unit_cost,
                    'sum'       => $sum,
                ];
            }

            $document = new \PhpOffice\PhpWord\TemplateProcessor(app_path() . '/Modules/BusinessTrip/Doc_templates/'.$file_name.'.docx');
            $document->setValue('company_name',$company);
            $document->setValue('fio',$FIO);
            $document->setValue('position',$position);
            $document->setValue('date_start',$date_start->format('d.m.Y'));
            $document->setValue('date_stop',$date_final->format('d.m.Y'));
            $document->setValue('city_start',$city_start['name'] . ' ( ' . $city_start['region'] . ' )');
            $document->setValue('city_stop',$city_final['name'] . ' ( ' . $city_final['region'] . ' )');
            $document->setValue('gsm',$gasoline);
            $document->setValue('punkt_city',$route);

            //ниже реквизиты банка
            $document->setValue('rs',   ($bank_info['rs'] ? $bank_info['rs'] : ''));
            $document->setValue('bank', ($bank_info['bank'] ? $bank_info['bank'] : ''));
            $document->setValue('addr', ($bank_info['addr'] ? $bank_info['addr'] : ''));
            $document->setValue('ks',   ($bank_info['ks'] ? $bank_info['ks'] : ''));
            $document->setValue('bik',  ($bank_info['bik'] ? $bank_info['bik'] : ''));

            $obPHPWord = new \PhpOffice\PhpWord\PhpWord();
            //$section   = $obPHPWord->addSection();
            $section   = $obPHPWord->createSection();

            $table = $section->addTable([
                'borderSize' => 2,
                'borderColor' => '000000',
                'alignment'=> \PhpOffice\PhpWord\SimpleType\JcTable::CENTER,
                'layout'=> \PhpOffice\PhpWord\Style\Table::LAYOUT_FIXED
            ]);
            $table->addRow(800);
            $table->addCell(530, ['valign' => 'center'])->addText(
                '№<w:br/>п/п<w:br/>',
                ['size' => 8,'bold' => true,'name' => 'Times New Roman'],
                ['hanging' => 0,'alignment'=>\PhpOffice\PhpWord\SimpleType\JcTable::CENTER]
            );
            $table->addCell(3700, ['valign' => 'center'])->addText(
                'Статьи затрат',
                ['size' => 8,'bold' => true,'name' => 'Times New Roman'],
                ['hanging' => 0,'alignment'=>\PhpOffice\PhpWord\SimpleType\JcTable::CENTER]
            );
            $table->addCell(1500, ['valign' => 'center'])->addText(
                'Ед. изм.',
                ['size' => 8,'bold' => true,'name' => 'Times New Roman'],
                ['hanging' => 0,'alignment'=>\PhpOffice\PhpWord\SimpleType\JcTable::CENTER]
            );
            $table->addCell(1500, ['valign' => 'center'])->addText(
                'Кол-во',
                ['size' => 8,'bold' => true,'name' => 'Times New Roman'],
                ['hanging' => 0,'alignment'=>\PhpOffice\PhpWord\SimpleType\JcTable::CENTER]
            );
            $table->addCell(1500, ['valign' => 'center'])->addText(
                'Цена ед.',
                ['size' => 8,'bold' => true,'name' => 'Times New Roman'],
                ['hanging' => 0,'alignment'=>\PhpOffice\PhpWord\SimpleType\JcTable::CENTER]
            );
            $table->addCell(1500, ['valign' => 'center'])->addText(
                'Сумма',
                ['size' => 8,'bold' => true,'name' => 'Times New Roman'],
                ['hanging' => 0,'alignment'=>\PhpOffice\PhpWord\SimpleType\JcTable::CENTER]
            );

            $cnt = 0;
            foreach($estimate as $el){
                $table->addRow(800);
                $table->addCell(530, ['bold' => true,'valign' => 'center'])->addText(
                    ++ $cnt,
                    ['size' => 8,'name' => 'Times New Roman'],
                    ['hanging' => 0,'alignment'=>\PhpOffice\PhpWord\SimpleType\JcTable::CENTER]
                );
                $table->addCell(3700, ['valign' => 'center'])->addText(
                    $el['name'],
                    ['size' => 8,'name' => 'Times New Roman'],
                    ['hanging' => 0,'alignment'=>\PhpOffice\PhpWord\SimpleType\JcTable::CENTER]
                );
                $table->addCell(1500, ['valign' => 'center'])->addText(
                    $el['name_unit'],
                    ['size' => 8,'name' => 'Times New Roman'],
                    ['hanging' => 0,'alignment'=>\PhpOffice\PhpWord\SimpleType\JcTable::CENTER]
                );
                $table->addCell(1500, ['valign' => 'center'])->addText(
                    $el['count'],
                    ['size' => 8,'name' => 'Times New Roman'],
                    ['hanging' => 0,'alignment'=>\PhpOffice\PhpWord\SimpleType\JcTable::CENTER]
                );
                $table->addCell(1500, ['valign' => 'center'])->addText(
                    $el['cost'] . 'руб.',
                    ['size' => 8,'name' => 'Times New Roman'],
                    ['hanging' => 0,'alignment'=>\PhpOffice\PhpWord\SimpleType\JcTable::CENTER]
                );
                $table->addCell(1500, ['valign' => 'center'])->addText(
                    $el['sum']  . 'руб.',
                    ['size' => 8,'name' => 'Times New Roman'],
                    ['hanging' => 0,'alignment'=>\PhpOffice\PhpWord\SimpleType\JcTable::CENTER]
                );
            }

            $table->addRow(800);
            $table->addCell(530, ['valign' => 'center']);
            $table->addCell(8200, ['gridSpan' => 4,'valign' => 'center'])->addText(
                'Итого',
                ['size' => 8,'bold' => true,'name' => 'Times New Roman'],
                ['hanging' => 0,'alignment'=>\PhpOffice\PhpWord\SimpleType\JcTable::CENTER]
            );
            $table->addCell(1500, ['valign' => 'center'])->addText(
                $total_estimate . 'руб.',
                ['size' => 8,'bold' => true,'name' => 'Times New Roman'],
                ['hanging' => 0,'alignment'=>\PhpOffice\PhpWord\SimpleType\JcTable::CENTER]
            );

            $sTableText = $this->getTableAsText($table, $obPHPWord);
            $document->setValue('table', $sTableText);
            $document->saveAs(app_path() . '/Modules/BusinessTrip/tmp/'. $file_name . '_' . $task_id . '.docx');

            $path = app_path() . '/Modules/BusinessTrip/tmp/'. $file_name . '_' . $task_id . '.docx';
            return response()->file($path);
        }

         //ниже создаем файл с маршрутным листом
         if ($type_file == 'ml' && $auto_travel){
             $file_name = 'ml';
             $route     = '';
             foreach ($transit_dots as $dot){
                 $route ?
                     $route = $route . ', ' . $dot['name'] . ' ( ' . $dot['region'] . ' )':
                     $route = $dot['name'] . ' ( ' . $dot['region'] . ' )';
             };

             $document = new \PhpOffice\PhpWord\TemplateProcessor(app_path() . '/Modules/BusinessTrip/Doc_templates/'.$file_name.'.docx');
             $document->setValue('company',$company);
             $document->setValue('department',$department);
             $document->setValue('position',$position);
             $document->setValue('fio',$FIO);
             $document->setValue('date_start',$date_start->format('d.m.Y'));
             $document->setValue('date_final',$date_final->format('d.m.Y'));
             $document->setValue('city_start',$city_start['name'] . ' ( ' . $city_start['region'] . ' )');
             $document->setValue('city_stop',$city_final['name'] . ' ( ' . $city_final['region'] . ' )');
             $document->setValue('gsm',$gasoline);
             $document->setValue('gsm_final', $total_GSM);
             $document->setValue('gsm',$gasoline);
             $document->setValue('punkt_city',$route);

             $obPHPWord = new \PhpOffice\PhpWord\PhpWord();
             $section   = $obPHPWord->addSection();
             //$section   = $obPHPWord->createSection();
             $table     = $section->addTable([
                 'borderSize' => 2,
                 'borderColor' => '000000',
                 'alignment'=> \PhpOffice\PhpWord\SimpleType\JcTable::CENTER,
                 'layout'=> \PhpOffice\PhpWord\Style\Table::LAYOUT_FIXED
             ]);

             $table->addRow(800);
             $table->addCell(850, ['valign' => 'center'])->addText(
                 '№<w:br/>п/п<w:br/>',
                 ['size' => 9.5,'bold' => true,'name' => 'Times New Roman'],
                 ['hanging' => 0,'alignment'=>\PhpOffice\PhpWord\SimpleType\JcTable::CENTER]
             );
             $table->addCell(3400, ['valign' => 'center'])->addText(
                 'Статья затрат',
                 ['size' => 8,'bold' => true,'name' => 'Times New Roman'],
                 ['hanging' => 0,'alignment'=>\PhpOffice\PhpWord\SimpleType\JcTable::CENTER]
             );
             $table->addCell(2000, ['valign' => 'center'])->addText(
                 'Численность населения, тыс. чел.',
                 ['size' => 8,'bold' => true,'name' => 'Times New Roman'],
                 ['hanging' => 0,'alignment'=>\PhpOffice\PhpWord\SimpleType\JcTable::CENTER]
             );
             $table->addCell(1400, ['valign' => 'center'])->addText(
                 'Лимит км в городе, км',
                 ['size' => 8,'bold' => true,'name' => 'Times New Roman'],
                 ['hanging' => 0,'alignment'=>\PhpOffice\PhpWord\SimpleType\JcTable::CENTER]
             );
             $table->addCell(1300, ['valign' => 'center'])->addText(
                 'Дата',
                 ['size' => 8,'bold' => true,'name' => 'Times New Roman'],
                 ['hanging' => 0,'alignment'=>\PhpOffice\PhpWord\SimpleType\JcTable::CENTER]
             );
             $table->addCell(1150, ['valign' => 'center'])->addText(
                 'Количество дней в городе, д',
                 ['size' => 8,'bold' => true,'name' => 'Times New Roman'],
                 ['hanging' => 0,'alignment'=>\PhpOffice\PhpWord\SimpleType\JcTable::CENTER]
             );
             $table->addCell(1300, ['valign' => 'center'])->addText(
                 'Кол-во км трансфера между городами. км',
                 ['size' => 8,'bold' => true,'name' => 'Times New Roman'],
                 ['hanging' => 0,'alignment'=>\PhpOffice\PhpWord\SimpleType\JcTable::CENTER]
             );
             $table->addCell(1100, ['valign' => 'center'])->addText(
                 'Итого расстояние, км',
                 ['size' => 8,'bold' => true,'name' => 'Times New Roman'],
                 ['hanging' => 0,'alignment'=>\PhpOffice\PhpWord\SimpleType\JcTable::CENTER]
             );
             $table->addCell(1450, ['valign' => 'center'])->addText(
                 'Итого сумма, руб',
                 ['size' => 8,'bold' => true,'name' => 'Times New Roman'],
                 ['hanging' => 0,'alignment'=>\PhpOffice\PhpWord\SimpleType\JcTable::CENTER]
             );

             $cnt = 0;
             foreach ($route_sheet  as $idx => $el){
                 $table->addRow(700);
                 $table->addCell(850, ['valign' => 'center'])->addText(
                     ($idx != count($route_sheet) - 1) ?  $cnt+1 : '',
                     ['size' => 9.5,'bold' => true,'name' => 'Times New Roman'],
                     ['hanging' => 0,'alignment'=>\PhpOffice\PhpWord\SimpleType\JcTable::CENTER]
                 );
                 $table->addCell(3400, ['valign' => 'center'])->addText(
                     $el['route'],
                     ['size' => 8,'name' => 'Times New Roman'],
                     ['hanging' => 0,'alignment'=>\PhpOffice\PhpWord\SimpleType\JcTable::CENTER]
                 );
                 $table->addCell(2000, ['valign' => 'center'])->addText(
                     $el['population'],
                     ['size' => 8,'name' => 'Times New Roman'],
                     ['hanging' => 0,'alignment'=>\PhpOffice\PhpWord\SimpleType\JcTable::CENTER]
                 );
                 $table->addCell(1400, ['valign' => 'center'])->addText(
                     $el['km'] > 0 ? $el['km'].' км.' : '',
                     ['size' => 8,'name' => 'Times New Roman'],
                     ['hanging' => 0,'alignment'=>\PhpOffice\PhpWord\SimpleType\JcTable::CENTER]
                 );
                 $table->addCell(1300, ['valign' => 'center'])->addText(
                     $el['date'] > 0 ? $el['date'] : '',
                     ['size' => 8,'name' => 'Times New Roman'],
                     ['hanging' => 0,'alignment'=>\PhpOffice\PhpWord\SimpleType\JcTable::CENTER]
                 );
                 $table->addCell(1150, ['valign' => 'center'])->addText(
                     $el['days'] > 0 ? $el['days'].' дней' : '',
                     ['size' => 8,'name' => 'Times New Roman'],
                     ['hanging' => 0,'alignment'=>\PhpOffice\PhpWord\SimpleType\JcTable::CENTER]
                 );
                 $table->addCell(1300, ['valign' => 'center'])->addText(
                     $el['distance'] > 0 ? $el['distance'].' км.' : '',
                     ['size' => 8,'name' => 'Times New Roman'],
                     ['hanging' => 0,'alignment'=>\PhpOffice\PhpWord\SimpleType\JcTable::CENTER]
                 );
                 $table->addCell(1100, ['valign' => 'center'])->addText(
                     $el['total_distance'] > 0 ? $el['total_distance'] .' км.' : '',
                     ['size' => 8,'name' => 'Times New Roman'],
                     ['hanging' => 0,'alignment'=>\PhpOffice\PhpWord\SimpleType\JcTable::CENTER]
                 );
                 $table->addCell(1450, ['valign' => 'center'])->addText(
                     $el['sum'] > 0 ? $el['sum'].' руб.' : '',
                     ['size' => 8,'name' => 'Times New Roman'],
                     ['hanging' => 0,'alignment'=>\PhpOffice\PhpWord\SimpleType\JcTable::CENTER]
                 );
                 $cnt++;
             }

             $sTableText = $this->getTableAsText($table, $obPHPWord);
             $document->setValue('table', $sTableText);
             $document->saveAs(app_path() . '/Modules/BusinessTrip/tmp/'. $file_name . '_' . $task_id . '.docx');

             $path = app_path() . '/Modules/BusinessTrip/tmp/'. $file_name . '_' . $task_id . '.docx';
             return response()->file($path);
         }
    }

    public function delete(Request $request){
        if(!$request->task_id)
            return response()->json(['status' => 'error', 'message' => 'Возникла ошибка, нет id задания']);

        if(!$request->file_name)
            return response()->json(['status' => 'error', 'message' => 'Возникла ошибка, нет имени файла']);

        $task_id         = $request->task_id;
        $file_name       = $request->file_name;

        if(file_exists(app_path() . '/Modules/BusinessTrip/tmp/'. $file_name . '_' . $task_id . '.docx')){
            unlink(app_path() . '/Modules/BusinessTrip/tmp/'. $file_name . '_' . $task_id . '.docx');
        }
        return response()->json(['status' => 'success']);
    }
}

