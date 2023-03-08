<?php
namespace App\Modules\BusinessTrip\Controllers;

use App\Modules\BusinessTrip\Action\FileAction;
use App\Modules\BusinessTrip\Model\File;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;

class FilesController extends Controller{
    public function add(Request $request){
        if(!$request->task_id){
            return response()->json(['status' => 'error']);
        }
        $newFile = new File();

        $newFile_id = FileAction::saveFile($newFile, 'Scans', $request);

        return response()->json(['status' => 'success', 'file_id' => $newFile_id]);
    }

    public function edit(Request $request){
//        if(!$request->scan_id || $request->scan_id == 0){
//            return response()->json(['status' => 'error']);
//        }
//        $scan = Scan::find($request->scan_id);
//        ScanAction::setScan($scan, $request);
//        $fileId = '';
//        if($request->file('file')){
//            $filesId = $scan->file->pluck('id');
//            foreach($filesId as $file_id){
//                FileAction::deleteFile($file_id);
//            }
//            $fileId = FileAction::saveFile($request->file, 'Scans', Scan::class, $scan->id);
//        }
//        return response()->json(['status' => 'success', 'id' => $scan->id, 'file_id' => $fileId]);
    }

    public function load(Request $request)
    {
        $file = File::find($request->file_id);

        $path = app_path().$file->dir.'/'.$file->hash_name;
        return response()->file($path);
    }

}
