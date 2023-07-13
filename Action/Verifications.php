<?php

namespace App\Modules\BusinessTrip\Action;

use App\Modules\BusinessTrip\Model\User;
use App\Modules\BusinessTrip\Model\Department;
use App\Modules\BusinessTrip\Model\Task;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class Verifications{

    public static function allDepartments():Collection{
        return DB::table('b_iblock_section')
            ->join('b_uts_iblock_5_section', 'b_iblock_section.ID', '=', 'b_uts_iblock_5_section.VALUE_ID')
            ->where([['b_iblock_section.IBLOCK_ID', 5]])
            ->select('b_iblock_section.ID', 'b_iblock_section.NAME','b_iblock_section.IBLOCK_SECTION_ID', 'b_uts_iblock_5_section.UF_HEAD as HEAD')
            ->get();
    }

    public static function allUsers():Collection{
        return DB::table('b_user')
            ->where('ACTIVE', '=', 'Y')
            ->join('b_utm_user', 'b_user.ID', '=', 'b_utm_user.VALUE_ID')
            ->select('b_user.ID','b_user.ACTIVE', 'b_user.NAME', 'b_user.LAST_NAME',
                'b_user.SECOND_NAME', 'b_user.XML_ID', 'b_utm_user.VALUE_INT as DEPARTMENT')
            ->where([['b_utm_user.FIELD_ID', 41]])
            ->get();
    }

    public static function userDepartment($user_id){
        $depId = DB::table('b_user')
            ->join('b_utm_user', 'b_user.ID', '=', 'b_utm_user.VALUE_ID')
            ->select('b_user.ID','b_user.ACTIVE', 'b_user.NAME', 'b_user.LAST_NAME',
                'b_user.SECOND_NAME', 'b_user.XML_ID', 'b_utm_user.VALUE_INT as DEPARTMENT')
            ->where([['b_utm_user.FIELD_ID', 41], ['b_user.ID', $user_id]])
            ->first()->DEPARTMENT;
        return Department::find($depId);
    }

    public static function checkTaskAccess($model, $user_id){
        $userModel = User::find($user_id);

        $roles = $userModel->roles()->pluck('name');

        if(in_array('admin', $roles->toArray())){
            return $model->where([['id', '>', 1]]);
        }else{

            $allDeps = self::allDepartments();

            $isBossDep = $allDeps->where('HEAD', $user_id)->pluck('ID')->first();

            if($isBossDep){
                $departmentsId = [$isBossDep];
                $childDepartment = $allDeps
                    ->whereIn('IBLOCK_SECTION_ID', $isBossDep)
                    ->pluck('ID');

                while($childDepartment->count() > 0){

                    foreach($childDepartment as $el){
                        $departmentsId[] = $el;
                    }
                    $childDepartment = $allDeps
                        ->whereIn('IBLOCK_SECTION_ID', $childDepartment)
                        ->pluck('ID');
                }

                $usersIds = self::allUsers()
                    ->whereIn('DEPARTMENT', $departmentsId)
                    ->pluck('ID')->toArray(); //->toArray()

                $usersIds[] = $user_id;


            }else{
                $usersIds = [$user_id];
            }
//начало нового
            foreach($roles as $role){

                if($role=='additional'){
                    $departments = $userModel->additionalRightsDepartments;

                    if(!empty($departments)){

                        $departmentsId = $departments->pluck('ID')->toArray();

                        $allDeps = self::allDepartments();

                        $childDepartment = $allDeps
                            ->whereIn('IBLOCK_SECTION_ID', $departmentsId)
                            ->pluck('ID');

                        while($childDepartment->count() > 0){

                            foreach($childDepartment as $el){

                                $departmentsId[] = $el;
                            }
                            $childDepartment = $allDeps
                                ->whereIn('IBLOCK_SECTION_ID', $childDepartment)
                                ->pluck('ID');
                        }

                        $usersIds = array_merge(
                            $usersIds,
                            self::allUsers()
                                ->whereIn('DEPARTMENT', $departmentsId)
                                ->pluck('ID')->toArray()
                        );

                    }

                    $individuals = $userModel->additionalRightsUsers;

                    if($individuals){
                        $usersIds = array_merge($usersIds ,$individuals->pluck('ID')->toArray());
                    }
                }

            }
//конец нового
        }

        $usersIds = array_unique($usersIds);

        $ids            = \App\Modules\Process\Controllers\ProcessDocumentController::getIdsDocumentBp($user_id, 'BusinessTrip');

        $model = $model->where(function($query) use ($usersIds, $ids) {
            $query->whereIn('responsible_id', $usersIds)
                ->orWhereIn('id', $ids);
        });


        return $model;
    }

    public static function checkAccess($taskModel, $user_id){
        $userModel = User::find($user_id);

        $roles = $userModel->roles()->pluck('name');

        if(
            in_array('admin', $roles->toArray())
            ||
            $taskModel->responsible_id == $user_id
            ||
            in_array($taskModel->id,\App\Modules\Process\Controllers\ProcessDocumentController::getIdsDocumentBp($user_id, 'BusinessTrip'))
            ||
            in_array($taskModel->responsible_id, $userModel->additionalRightsUsers->pluck('ID')->toArray())
        ) return $taskModel;

        $additionalRightsDepartments = $userModel->additionalRightsDepartments;
        if(!empty($additionalRightsDepartments)){

            foreach($additionalRightsDepartments as $dep){

                $depHierarchy = self::departmentsHierarchy($dep->ID);

                if(in_array($taskModel->department_id, $depHierarchy))
                    return $taskModel;

            }
        }

        $allDeps = self::allDepartments();
        $isBossDep = $allDeps->firstWhere('HEAD', $user_id)??'';
        if($isBossDep){

            $depHierarchy = self::departmentsHierarchy($isBossDep->ID);

            if(in_array($taskModel->department_id, $depHierarchy))
                return $taskModel;

        }
        return false;
    }

    public static function departmentsHierarchy($dep_id){
        $departmentsId = [$dep_id];
        $allDeps = self::allDepartments();

        $childDepartment = $allDeps
            ->whereIn('IBLOCK_SECTION_ID', $dep_id)
            ->pluck('ID');

        while($childDepartment->count() > 0){

            foreach($childDepartment as $el){

                $departmentsId[] = $el;
            }
            $childDepartment = $allDeps
                ->whereIn('IBLOCK_SECTION_ID', $childDepartment)
                ->pluck('ID');
        }
        return $departmentsId;
    }

    public static function checkFullAccess($user_id, $responsible_id){
        $additionalRightsUser = DB::table('l_business_trip_additional_rights')->where([
            ['entity_type', '=', User::class],
            ['user_id',     '=', $user_id],
            ['entity_id',   '=', $responsible_id]
        ])->first();

        if($additionalRightsUser){

            return $additionalRightsUser->full_access;
        }

        $additionalRightsDepartments = DB::table('l_business_trip_additional_rights')->where([
            ['entity_type', '=', Department::class],
            ['user_id',     '=', $user_id]
        ])->get();

        foreach($additionalRightsDepartments->pluck('entity_id') as $dep_id) {

            $departmentHierarchy = self::departmentsHierarchy($dep_id);

            $usersInDepartments = self::allUsers()
                ->whereIn('DEPARTMENT', $departmentHierarchy);

            $checkExecutorInUsersList = $usersInDepartments
                ->filter(function($item) use ($responsible_id) {return $item->ID == $responsible_id;})
                ->pluck('DEPARTMENT')
                ->first();

            if($checkExecutorInUsersList){

                $finalCheck = $additionalRightsDepartments->where('entity_id', $dep_id)->first();
                return $finalCheck->full_access;
            }
        }

        return 1;
    }

}

