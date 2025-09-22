<?php

namespace App\Libraries;

use GuzzleHttp\Exception\GuzzleException;
use GuzzleHttp\Client as GuzzleClient;
use Illuminate\Support\Facades\Cookie;

class RolePermission {   

    public static function canAccess($section, $required_permissions, $user_id) {
//        $admin_id = session()->get('admin_id');
        if ($user_id) {

            $employementData = \App\Models\EmployementData::select('role_permission')->where('user_id', $user_id)->first();
            if ($employementData) {
                $role_option = \App\Models\RoleOption::where('role_id', $employementData->role_permission)->where('option_name', $section)->first();
                if ($role_option->$required_permissions) {
                    return true;
                } else {
                    return false;
                }
            }
        }
        return false;
    }

}
