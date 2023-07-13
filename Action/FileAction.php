<?php
namespace App\Modules\BusinessTrip\Action;

use App\Modules\BusinessTrip\Model\File;
use Illuminate\Support\Str;

class FileAction{
    public static function saveFile($task_id, $model, $dir, $type, $file){
        $dir = '/Modules/BusinessTrip/Files/' . $dir;

        $original = str_replace('.' . $file->getClientOriginalExtension(),
            '', $file->getClientOriginalName());
        $translated = Str::slug($original, '_');

        $hash = md5($translated . date('YmdHis') . $type);

        $file->move(app_path() . $dir, $hash);

        $model->task_id          = $task_id;
        $model->type             = $type;
        $model->original_name    = $original;
        $model->translated_name  = $translated;
        $model->hash_name        = $hash;
        $model->dir              = $dir;
        $model->type_file        = $file->getClientOriginalExtension();
        $model->save();

        return $model->id;
    }

    public static function deleteFile($task_id, $type){
        $file = File::where([ ['task_id', '=', $task_id], ['type', '=', $type] ])->first();
        if($file){
            if(file_exists(app_path() . $file->dir . '/' . $file->hash_name)){
                unlink(app_path() . $file->dir . '/' . $file->hash_name);
                $file->delete();
            }
        }
    }
}
