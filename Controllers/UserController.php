<?php
namespace App\Modules\BusinessTrip\Controllers;

use App\Http\Controllers\Controller;

use App\Modules\BusinessTrip\Action\Verifications;
use App\Modules\BusinessTrip\Model\User;
use App\Modules\BusinessTrip\Model\Company;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;

class UserController extends Controller {

    public function get(Request $request){

        if(!is_numeric($request->user_id))
            return response()->json(['status' => 'error', 'message' => 'Возникла ошибка с user_id']);
        $user = User::with(['roles'])->find($request->user_id);

        $response = Http::withBasicAuth('bitrix','78523')
            ->post('http://c1-it-s-1c/ZUP/hs/crm/API1/PersonalAccounts', [
                'FIZ_LICO' => $user->XML_ID,
            ]);

        //return $response;

        $answer = $response->json();
        $company_XML = [];
        $XML_accountNumber  = [];

        if(!$answer['error']){
            foreach ($answer['result'] as $el){
                $company_XML[]       = $el['organization_GUID'];
                $XML_accountNumber[] =  [
                    'organization_GUID' => $el['organization_GUID'],
                    'accountNumber'     => $el['accountNumber']
                ];
            }
        }else{return 'Ошибка с 1С' . $response->error;}

        $company = Company::where('IBLOCK_ID',38)
            ->where('ACTIVE', 'Y')
            ->whereIn('XML_ID',$company_XML)->get()->map(function($el){
                return [
                    'id'            => $el->ID,
                    'XML_ID'        => $el->XML_ID,
                    'name'          => $el->NAME,
                    'accountNumber' => [],
                ];
            })->toArray();

        foreach ($XML_accountNumber as $XML) {
            foreach ($company as $key => $value){
                if ($value['XML_ID'] == $XML['organization_GUID']){
                    $company[$key]['accountNumber'][] = $XML['accountNumber'];
                    break;
                }
            }
        }

        if (count($company) == 0){
            $company[] = [
                'id'            => 'Нет данных от 1С',
                'company_XML'   => 'Нет данных от 1С',
                'accountNumber' => [],
                'name'          => 'Нет данных от 1С',
            ];
        }

        $department = Verifications::userDepartment($request->user_id);

        $roles = ($user->roles->isNotEmpty())?$user->roles->pluck('name'):[];

        return response()->json([
            'status'    => 'success',
            'data'      => [
                'user'    => [
                    'id'             => $user->ID,
                    'FIZ_LICO'       => $user->XML_ID,
                    'FIO'            => $user->full_name,
                    'company'        => $company,
                    'department'     => ['id' => $department->ID ?: null, 'name' => $department->NAME ?: null],
                    'position'       => $user->WORK_POSITION ?: null,
                ],
                'roles'    => $roles,
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
