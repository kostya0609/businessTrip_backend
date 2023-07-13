<?php
namespace App\Modules\BusinessTrip\Controllers;

use App\Modules\BusinessTrip\Action\FileAction;
use App\Modules\BusinessTrip\Model\File;
use App\Modules\BusinessTrip\Model\Log;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;

class FilesController extends Controller{
    public function update(Request $request){
        if(!$request->task_id || !$request->user_id){
            return response()->json(['status' => 'error']);
        }

        $task_id = $request->task_id;
        $user_id = $request->user_id;

        $file_save = [];
        if(isset($request->file_save) && count($request->file_save) > 0){
            $file_save = $request->file_save;
        }

        $file_exists = File::where([['task_id', '=', $task_id], ['type', '=', 'additional']])->get();

        $deleteFile = array_diff($file_exists->pluck('id')->toArray(),$file_save);

        foreach ($file_exists as $file){
            if(in_array($file->id,$deleteFile)){
                unlink(app_path() . $file->dir . '/' . $file->hash_name);
                $file->delete();
            }
        }

        if(isset($request->file) && count($request->file) > 0){
            foreach ($request->file as $item){
                $newFile = new File();
                FileAction::saveFile($task_id, $newFile, 'Additional', 'additional', $item);
            }
        };

        $file_exists = File::where([['task_id', '=', $task_id], ['type', '=', 'additional']])->select('id', 'original_name', 'type_file')->get();

        $file_exists = $file_exists->map(function ($file){
            return [
                'id'   => $file->id,
                'name' => $file->original_name,
                'type' => $file->type_file,

            ];
        });

        $log = new Log();
        $logMessage = 'Дополнительные файлы для задание по командировки обновлены.';
        $log->setLog(
            $task_id,
            $user_id,
            $logMessage
        );

        return response()->json(['status' => 'success', 'file_save' => $file_exists]);
    }

    public function get(Request $request){

        if(!$request->task_id){
            return response()->json(['status' => 'error']);
        }
        $task_id = $request->task_id;

        if (!$request->type || $request->type === 'all'){
            $file_save = File::where('task_id', '=', $task_id)->select('id', 'original_name')->get();
        }else{
            $file_save = File::where([['task_id', '=', $task_id], ['type', '=', $request->type]])->select('id', 'original_name')->get();
        };

        $file_save = $file_save->map(function ($file){
            return [
                'id'   => $file->id,
                'name' => $file->original_name,
                'type' => $file->type_file,
            ];
        });

        return response()->json(['status' => 'success', 'file_save' => $file_save]);
    }

    public function load(Request $request)    {
        $file = File::find($request->file_id);

        $path = app_path().$file->dir.'/'.$file->hash_name;

        return response()->file($path);
    }

}
