<?php
namespace App\Modules\BusinessTrip\Controllers;

use App\Http\Controllers\Controller;

use App\Modules\BusinessTrip\Action\Verifications;
use App\Modules\BusinessTrip\Model\User;
use App\Modules\BusinessTrip\Model\Company;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;


class UserController extends Controller {

    public function get(Request $request){
        if(!is_numeric($request->user_id))
            return response()->json(['status' => 'error', 'message' => 'Возникла ошибка с user_id']);

        $user = User::find($request->user_id);

        $response = Http::withBasicAuth('bitrix','78523')
            ->get('http://c-it-s-1c/upp/hs/crm/getpasport', [
                'paramcatalog1' => $user->XML_ID,
            ]);
        $obj = json_decode(substr($response, strpos($response, "[")));
        $companyXml = $obj[0]->Организаниция->GUID;
        $company = Company::where('IBLOCK_ID',38)
            ->where('XML_ID',$companyXml)
            ->first();

        $department = Verifications::userDepartment($request->user_id);

        //$roles = ($user->roles->isNotEmpty())?$user->roles->pluck('name'):[];

        return response()->json([
            'status'    => 'success',
            'data'      => [
                'user'    => [
                    'id'         => $user->ID,
                    'fio'        => $user->full_name,
                    'company'    => ['id' => $company->ID ? $company->ID : null , 'name' => $company->NAME ? $company->NAME : null],
                    'department' => ['id' => $department->ID ? $department->ID : null, 'name' => $department->NAME ? $department->NAME : null],
                    'position'   => $user->WORK_POSITION ? $user->WORK_POSITION : null,
                ],
                'roles'   => ['admin'],
                'obj'     => $obj[0],
                //'roles'    => $roles,
            ]
        ]);
    }

    public function list(Request $request){
        return response()->json(['status' => 'success']);
    }

    public function add(Request $request){
        return response()->json(['status' => 'success']);
    }

    public function delete(Request $request){
        return response()->json(['status' => 'success']);
    }

}
