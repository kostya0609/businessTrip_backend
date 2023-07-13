<?php

namespace App\Modules\BusinessTrip\Controllers;
use App\Http\Controllers\Controller;
use App\Modules\BusinessTrip\Model\CostList;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use App\Modules\BusinessTrip\Action\TaskAction;
use App\Modules\BusinessTrip\Action\Verifications;
use App\Modules\BusinessTrip\Action\Filter;
use App\Modules\BusinessTrip\Action\FileAction;
use App\Modules\BusinessTrip\Action\Translate;
use App\Modules\BusinessTrip\Model\Task;
use App\Modules\BusinessTrip\Model\NeedAction;
use App\Modules\BusinessTrip\Model\City;
use App\Modules\BusinessTrip\Model\User;
use App\Modules\BusinessTrip\Model\Company;
use App\Modules\BusinessTrip\Model\Department;
use App\Modules\BusinessTrip\Model\TaskDot;
use App\Modules\BusinessTrip\Model\TaskTarget;
use App\Modules\BusinessTrip\Model\Log;
use App\Modules\BusinessTrip\Model\File;
use Illuminate\Validation\Rule;


class TaskController extends Controller {
    public function validation($data){
        $validation = Validator::make($data->all(),
            [
                'user_id'              => 'required | numeric',
                'responsible_id'       => 'required | numeric',
                'company_id'           => 'required | numeric',
                'department_id'        => 'required | numeric',
                'position'             => 'required',
                'checking_account'     => 'required',
                'accountant_id'        => 'required',
                'city_start_id'        => 'required | numeric',
                'city_final_id'        => 'required | numeric',
                'date_start'           => 'required',
                'date_final'           => 'required',
                'dots'                 => 'required',
                'mark'                 => Rule::requiredIf($data['auto_travel']),
                'model'                => Rule::requiredIf($data['auto_travel']),
                'number'               => Rule::requiredIf($data['auto_travel']),
                'gasoline'             => Rule::requiredIf($data['auto_travel']),
                'back_distance'        => Rule::requiredIf($data['auto_travel']),
            ],
            [
                'user_id.required'              => 'Некорректный запрос, нет user_id',
                'responsible_id.required'       => 'Поле "Ответственный" обязательно!',
                'company_id.required'           => 'Поле "Организация" обязательно!',
                'department_id.required'        => 'Поле "Подразделение" обязательно!',
                'position.required'             => 'Поле "Должность" обязательно!',
                'checking_account_id.required'  => 'Поле "Расчетный счет" обязательно!',
                'accountant_id.required'        => 'Поле "ФИО бухгалтера" обязательно!',
                'city_start_id.required'        => 'Поле "Город выезда" обязательно!',
                'city_final_id.required'        => 'Поле "Город возврата" обязательно!',
                'date_start.required'           => 'Поле "Дата выезда" обязательно!',
                'date_final.required'           => 'Поле "Дата возврата" обязательно!',
                'dots.required'                 => 'Информация о точках посещения" обязательна!',
                'mark.required'                 => 'Поле "Марка авто" обязательно!',
                'model.required'                => 'Поле "Модель авто" обязательно!',
                'number.required'               => 'Поле "Гос номер" обязательно!',
                'gasoline.required'             => 'Поле "Стоимость бензина" обязательно!',
                'back_distance.required'        => 'Поле "Км до точки возврата" обязательно!',
            ]);

        if($validation->fails()){return $validation;}

        foreach ($data['dots'] as $dot){
            $validation = Validator::make(collect($dot)->all(),[
                'city_id'           => 'required | numeric',
                'days'              => 'required',
                'sort'              => 'required',
                'distance'          => Rule::requiredIf($data['auto_travel']),
                'targets'           => 'required',
            ],[
                'city_id.required'   => 'Поле "Посещаемый город ID" обязательно!',
                'days.required'      => 'Поле "Дней пребываания" обязательно!',
                'sort.required'      => 'Поле "Сортировка" обязательно!',
                'distance.required'  => 'Поле "Км до города" обязательно!',
                'targets.required'   => 'Информация о целях коммандировки" обязательна!',
            ]);
            if($validation->fails()){return $validation;}

            foreach ($dot->targets as $target){
                $validation = Validator::make(collect($target)->all(),[
                    'target_id'         => 'required | numeric',
                    'contractor'        => 'required',
                    'sort'              => 'required',
                ],[
                    'target_id.required'   => 'Поле "Цель посещения" обязательно!',
                    'contractor.required'  => 'Поле "Контрагент" обязательно!',
                    'sort.required'        => 'Поле "Сортировка" обязательно!',
                ]);
                if($validation->fails()){return $validation;}
            }
        }

        return $validation;
    }

    public function create(Request $request){
        $data = collect(json_decode($request->data));

//      ниже была проверка что эти файлы обязательно должны быть
//        if($data['auto_travel'] && (!$request->agreement_file || !$request->vicarious_file)){
//            return response()->json(['status' => 'error', 'message' => 'Возникла ошибка, нет файла Соглашения об использовании втомобиля в личных целях или нет файла доверенности']);
//        }


        if($data['over_budget'] && !$request->over_budget_file){
            return response()->json(['status' => 'error', 'message' => 'Возникла ошибка, нет файла согласования командировки со сверх бюджетом']);
        }


        $validation = self::validation($data);

        if($validation->fails()){
            return response()->json([
                'status'    => 'error',
                'message'   => implode('<br>', $validation->errors()->all()),
            ]);
        }

        DB::beginTransaction();
        try {
            $newTask           = new Task();
            $newTask->status   = 'created';
            $newTask           = TaskAction::setTask($newTask, $data);
            $task_id           = $newTask->id;

            if ($data['auto_travel'] && $request->agreement_file) {
                $new_agreement_file = new File();
                FileAction::saveFile($task_id, $new_agreement_file, 'Scans', 'agreement',$request->agreement_file);
            }

            if ($data['auto_travel'] && $request->vicarious_file) {
                $new_vicarious_file = new File();
                FileAction::saveFile($task_id, $new_vicarious_file, 'Scans', 'vicarious',$request->vicarious_file);
            }

            if ($data['over_budget'] && $request->over_budget_file) {
                $new_over_budget_file = new File();
                FileAction::saveFile($task_id, $new_over_budget_file, 'Scans', 'over_budget',$request->over_budget_file);
            }

            $log = new Log();
            $logMessage = 'Задание для командировки создано';
            $log->setLog(
                $newTask->id,
                $data['user_id'],
                $logMessage
            );

            DB::commit();
            return response()->json(['status' => 'success', 'message' => 'Успешно', 'task_id'=> $task_id]);

        } catch (\Exception $e){
            DB::rollBack();
            return response()->json(['status' => 'error', 'message' => $e->getMessage()]);
        }
    }

    public function get(Request $request){

        $task_id = $request->task_id;
        $user_id = $request->user_id;

        if(!$task_id || !$user_id)
            return response()->json(['status' => 'error', 'message' => 'Возникла ошибка, нет user_id или нет task_id']);

        $taskModel = Task::with(['dots.targets', 'dots.city', 'files'])->find($task_id);

        $taskModel = Verifications::checkAccess($taskModel, $user_id);

        if(!$taskModel || !in_array($taskModel->status, ['created', 'fixing_problem']))
            return response()->json([
                'status'  => 'success',
                'message' => 'Отсутствует доступ для просмотра или задание нельзя редактировать в текущем статусе',
                'data'    => null
            ]);

        $responsible_name = User::find($taskModel->responsible_id)->full_name;

        $accountant_name  = User::find($taskModel->accountant_id)->full_name;

        $company_name     = Company::find($taskModel->company_id)->NAME;
        $department_name  = Department::find($taskModel->department_id)->NAME;

        $city_start_name = City::find($taskModel->city_start_id);
        $city_start_name = $city_start_name->name . ' (' .$city_start_name->region . ')';

        $city_final_name = City::find($taskModel->city_final_id);
        $city_final_name = $city_final_name->name . ' (' .$city_final_name->region . ')';

        $data = [
            'status'                  => $taskModel->status,
            'responsible_id'          => $taskModel->responsible_id,
            'responsible_list'        => [ ['value' => $taskModel->responsible_id, 'label' => $responsible_name] ],
            'accountant_id'           => $taskModel->accountant_id,
            'accountant_list'         => [ ['value' => $taskModel->accountant_id, 'label' => $accountant_name] ],
            'company_id'              => $taskModel->company_id,
//            'company_list'            => [ ['value' => $taskModel->company_id, 'label' => $company_name] ],
            'department_id'           => $taskModel->department_id,
//            'department_list'         => [ ['value' => $taskModel->department_id, 'label' => $department_name] ],
            'position'                => $taskModel->position,
            'checking_account'        => $taskModel->checking_account,
//            'checking_account_list'   => [ ['value' => $taskModel->checking_account, 'label' => $taskModel->checking_account] ],
            'comment'                 => $taskModel->comment,
            'city_start_id'           => $taskModel->city_start_id,
            'city_start_list'         => [ ['value' => $taskModel->city_start_id, 'label' => $city_start_name] ],
            'city_final_id'           => $taskModel->city_final_id,
            'city_final_list'         => [ ['value' => $taskModel->city_final_id, 'label' => $city_final_name] ],
            'date_start'              => $taskModel->date_start->format('d.m.Y'),
            'date_final'              => $taskModel->date_final->format('d.m.Y'),
            'auto_travel'             => $taskModel->auto_travel,
            'over_budget'             => $taskModel->over_budget,
            'mark'                    => $taskModel->mark,
            'model'                   => $taskModel->model,
            'number'                  => $taskModel->number,
            'gasoline'                => $taskModel->gasoline,
            'back_distance'           => $taskModel->back_distance,
            'document_link'           => $taskModel->document_link,
            'dots'                    => $taskModel->dots,
        ];

        if ($taskModel->auto_travel || $taskModel->over_budget){
            $files = [
                'agreementId'   => null,
                'agreementList' => [],
                'agreementFile' => null,

                'vicariousId'   => null,
                'vicariousList' => [],
                'vicariousFile' => null,

                'overBudgetId'   => null,
                'overBudgetList' => [],
                'overBudgetFile' => null,
             ];

            foreach ($taskModel->files as $file){
                if ($file->type == 'agreement') {
                    $files['agreementId']   = $file->id;
                    $files['agreementList'] = [ ['name' => $file->original_name, 'id' => $file->id, 'type' => $file->type_file] ];
                }

                if ($file->type == 'vicarious') {
                    $files['vicariousId']   = $file->id;
                    $files['vicariousList'] = [ ['name' => $file->original_name, 'id' => $file->id, 'type' => $file->type_file] ];
                }

                if ($file->type == 'over_budget') {
                    $files['overBudgetId']   = $file->id;
                    $files['overBudgetList'] = [ ['name' => $file->original_name, 'id' => $file->id, 'type' => $file->type_file] ];
                }
            }

            $data['files'] = $files;
        }

        return response()->json(['status' => 'success', 'message' => 'Успешно', 'data' => $data]);
    }

    public function detail(Request $request){

        $task_id = $request->task_id;
        $user_id = $request->user_id;

        if(!$task_id && !$user_id) return response()->json(['status' => 'error', 'message' => 'Возникла ошибка, нет user_id или нет task_id']);

        $taskModel = Task::with(['dots.targets.target', 'dots.city', 'files'])->find($task_id);

        $taskModel = Verifications::checkAccess($taskModel, $user_id);

        if(!$taskModel)
            return response()->json(['status' => 'success', 'message' => 'Отсутствует доступ для просмотра', 'data' => null]);

        $data = TaskAction::detailTask($taskModel, $request->user_id);;

        return response()->json(['status' => 'success', 'message' => 'Успешно', 'data' => $data]);
    }

    public function edit(Request $request){
        $data = collect(json_decode($request->data));

        $task_id = $data['task_id'];
        if(!is_numeric($task_id))
            return response()->json(['status' => 'error', 'message' => 'Возникла ошибка, нет task_id']);

//      ниже была проверка что эти файлы обязательно должны быть
//        if($data['auto_travel']){
//            if((!$request->agreement_file && !$request->agreement_file_id) || (!$request->vicarious_file && !$request->vicarious_file_id)){
//                return response()->json(['status' => 'error', 'message' => 'Возникла ошибка, нет файла Соглашения об использовании атомобиля в личных целях или нет файла доверенности']);
//            }
//        }

        if($data['over_budget']){
            if(!$request->over_budget_file && !$request->over_budget_file_id ){
                return response()->json(['status' => 'error', 'message' => 'Возникла ошибка, нет файла согласования командировки со сверх бюджетом']);
            }
        }

        $validation = self::validation($data);

        if($validation->fails()){
            return response()->json([
                'status'    => 'error',
                'message'   => implode('<br>', $validation->errors()->all()),
            ]);
        }

        DB::beginTransaction();
        try{
            TaskDot::where('task_id', '=', $task_id)->delete();
            TaskTarget::where('task_id', '=', $task_id)->delete();

            $taskModel = Task::find($task_id);

            if($taskModel->auto_travel && !$data['auto_travel']){
                CostList::where([['task_id','=', $task_id], ['cost_id', '=', 5]])->delete();
            }

            $taskModel = TaskAction::setTask($taskModel, $data);

            if($request->agreement_file && !$request->agreement_file_id){
                FileAction::deleteFile($task_id,'agreement');
                $new_agreement_file = new File();
                FileAction::saveFile($task_id, $new_agreement_file, 'Scans', 'agreement',$request->agreement_file);
            } elseif(!$request->agreement_file && !$request->agreement_file_id)
                FileAction::deleteFile($task_id,'agreement');

            if($request->vicarious_file && !$request->vicarious_file_id){
                FileAction::deleteFile($task_id,'vicarious');
                $new_vicarious_file = new File();
                FileAction::saveFile($task_id, $new_vicarious_file, 'Scans', 'vicarious',$request->vicarious_file);
            } elseif (!$request->vicarious_file && !$request->vicarious_file_id)
                FileAction::deleteFile($task_id,'vicarious');

            if($request->over_budget_file && !$request->over_budget_file_id){
                FileAction::deleteFile($task_id,'over_budget');
                $new_over_budget_file = new File();
                FileAction::saveFile($task_id, $new_over_budget_file, 'Scans', 'over_budget',$request->over_budget_file);
            } elseif (!$request->over_budget_file && !$request->over_budget_file_id){
                FileAction::deleteFile($task_id,'over_budget');
            }


            $log = new Log();
            $logMessage = 'Задание для командировки отредактировано';
            $log->setLog(
                $task_id,
                $data['user_id'],
                $logMessage
            );

            DB::commit();
            return response()->json(['status' => 'success', 'message' => 'Успешно', 'task_id' => $taskModel->id]);
        }catch (\Exception $e){
            DB::rollBack();
            return response()->json(['status' => 'error', 'message' => $e->getMessage()]);
        }
    }

    public function list(Request $request){
        $sort   = ($request->sort['name'])  ?: 'id';
        $order  = ($request->sort['order']) ?: 'asc';
        $limit  = $request->count;
        $offset = ($request->page - 1) * $limit;

        $taskModels = Task::orderBy($sort, $order);

        $taskModels = Verifications::checkTaskAccess($taskModels, $request->user_id);

        if($request->filter){
            $taskModels = Filter::filter($request->filter, $taskModels);
            $total = $taskModels->count();
        } else {
            $taskModels->whereNotIn('status',['canceled','completed']);
            $total = $taskModels->count();
        }

        $data = TaskAction::listGrid($taskModels->offset($offset)->limit($limit)->with('dots.city'), $request->user_id);

        return response()->json(['status' => 'success', 'message' => 'Успешно', 'data' => $data, 'total' => $total]);
    }

    public function listNeedAction(Request $request){
        $sort   = ($request->sort['name'])  ?: 'id';
        $order  = ($request->sort['order']) ?: 'asc';
        $limit  = $request->count;
        $offset = ($request->page - 1) * $limit;

        $tasksIds = NeedAction::where('user_id', $request->user_id)->pluck('task_id')->toArray();

        $taskModels = Task::orderBy($sort, $order)->whereIn('id', $tasksIds);

        $total = $taskModels->count();

        if($request->filter){
            $taskModels = Filter::filter($request->filter, $taskModels);
            $total = $taskModels->count();
        }

        $data = TaskAction::listGrid($taskModels->offset($offset)->limit($limit)->with('dots.city'), $request->user_id);
        return response()->json(['status' => 'success', 'message' => 'Успешно', 'data' => $data, 'total' => $total]);
    }

    public function delete(Request $request){
        $task_id = $request->task_id;

        if(!is_numeric($request->user_id) || !is_numeric($request->task_id) )
            return response()->json(['status' => 'error', 'message' => 'Возникла ошибка']);

        $taskModel = Task::find($task_id);

        DB::beginTransaction();
        try{
            TaskDot::where('task_id', '=', $task_id)->delete();
            TaskTarget::where('task_id', '=', $task_id)->delete();
            CostList::where('task_id', '=', $task_id)->delete();

            if($taskModel->auto_travel){
                FileAction::deleteFile($task_id,'agreement');
                FileAction::deleteFile($task_id,'vicarious');
            };

            if($taskModel->over_budget){
                FileAction::deleteFile($task_id,'over_budget');
            };

            $taskModel->delete();

            $log = new Log();
            $logMessage = 'Задание для командировки удалено';
            $log->setLog(
                $task_id,
                $request->user_id,
                $logMessage
            );

            DB::commit();
        }catch (\Exception $e){
            DB::rollBack();
            return response()->json(['status' => 'error', 'message' => $e->getMessage()]);
        }

        return response()->json(['status' => 'success', 'message' => 'Успешно']);
    }

    public function changeStatus(Request $request){
        $task_id = $request->task_id;

        if(!is_numeric($task_id) || !$request->user_id || !Translate::exists($request->status))
            return response()->json(['status' => 'error', 'message' => 'Возникла ошибка']);

        if($request->status == 'canceled' && !$request->cancel_comment)
            return response()->json(['status' => 'error', 'message' => 'Возникла ошибка']);

        $taskModel = Task::find($task_id);
        if($taskModel->status === $request->status)
            return response()->json(['status' => 'success', 'message' => 'Успешно']);


        DB::beginTransaction();
        try{
        $taskModel->status         = $request->status;
        $taskModel->cancel_comment = $request->status == 'canceled' ? $request->cancel_comment : '';
        $taskModel->save();

        $log = new Log();

        $logMessage = $request->status == 'canceled' ?
            'Задание для командировки переведено в статус - ' . Translate::translate($request->status) . " по причине $request->cancel_comment." :
            'Задание для командировки переведено в статус - ' . Translate::translate($request->status) . ' .';

        $log->setLog(
            $task_id,
            $request->user_id,
            $logMessage
        );

        DB::commit();
        return response()->json(['status' => 'success', 'message' => 'Успешно']);

        }catch (\Exception $e){
            DB::rollBack();
            return response()->json(['status' => 'error', 'message' => $e->getMessage()]);
        }
    }
}
