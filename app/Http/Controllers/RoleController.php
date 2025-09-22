<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Role;
use App\Models\RoleOption;

class RoleController extends Controller {

    public function index() {
        return Role::all();
    }

    public function get_role_permission(Request $request) {
        try {
            $statusCode = 422;
            $return = [];

            $user_id = $request->user->id;
            $employementData = \App\Models\User::select('role_permission')->where('id', $user_id)->first();
            if ($employementData) {
                $options = \App\Models\RoleOption::where('role_id', $employementData->role_permission)->get();

                $role_data = [];
                foreach ($options as $key => $val) {
                    $role_data[$val->option_name] = ['add_edit' => $val->add_edit, 'approved_reject' => $val->approved_reject, 'delete_void' => $val->delete_void, 'view_download' => $val->view_download];
                }
            }



            $statusCode = 200;
            $return['data'] = $role_data;
            $return['status'] = 'success';

            return $this->response($return, $statusCode);
        } catch (\Throwable $ex) {
            return $this->error(['status' => "error", 'main_error_message' => $ex->getMessage()]);
        }
    }

    public function store(Request $request) {
        try {
            $statusCode = 422;
            $return = [];
//           input_variable

            $title = $request->input('title');
            $level = $request->input('level');
            $announcement_data = $request->input('announcement_data');
            $employee_data = $request->input('employee_data');
            $notebook_data = $request->input('notebook_data');
            $roles_data = $request->input('roles_data');
            $report_data = $request->input('report_data');
            $setting_data = $request->input('setting_data');

            $role = new Role();

            $role->title = $title;
            $role->level = $level;

            if ($role->save()) {
                $option = new RoleOption;
                $option->role_id = $role->id;
                $option->option_name = "Announcement";
                $option->add_edit = $announcement_data['add_edit'];
                $option->view_download = $announcement_data['view_download'];
                $option->approved_reject = $announcement_data['approved_reject'];
                $option->delete_void = $announcement_data['delete_void'];
                $option->save();

                $option = new RoleOption;
                $option->role_id = $role->id;
                $option->option_name = "Employee";
                $option->add_edit = $employee_data['add_edit'];
                $option->view_download = $employee_data['view_download'];
                $option->approved_reject = $employee_data['approved_reject'];
                $option->delete_void = $employee_data['delete_void'];
                $option->save();

                $option = new RoleOption;
                $option->role_id = $role->id;
                $option->option_name = "Notebook";
                $option->add_edit = $notebook_data['add_edit'];
                $option->view_download = $notebook_data['view_download'];
                $option->approved_reject = $notebook_data['approved_reject'];
                $option->delete_void = $notebook_data['delete_void'];
                $option->save();

                $option = new RoleOption;
                $option->role_id = $role->id;
                $option->option_name = "Rolepermission";
                $option->add_edit = $roles_data['add_edit'];
                $option->view_download = $roles_data['view_download'];
                $option->approved_reject = $roles_data['approved_reject'];
                $option->delete_void = $roles_data['delete_void'];
                $option->save();

                $option = new RoleOption;
                $option->role_id = $role->id;
                $option->option_name = "Report";
                $option->add_edit = $report_data['add_edit'];
                $option->view_download = $report_data['view_download'];
                $option->approved_reject = $report_data['approved_reject'];
                $option->delete_void = $report_data['delete_void'];
                $option->save();

                $option = new RoleOption;
                $option->role_id = $role->id;
                $option->option_name = "Settings";
                $option->add_edit = $setting_data['add_edit'];
                $option->view_download = $setting_data['view_download'];
                $option->approved_reject = $setting_data['approved_reject'];
                $option->delete_void = $setting_data['delete_void'];
                $option->save();

                $return['data'] = $role;
                $return['options'] = RoleOption::where('role_id', $role->id)->get();
                $statusCode = 200;
                $return['status'] = 'success';
            }




            return $this->response($return, $statusCode);
        } catch (\Throwable $ex) {
            return $this->error(['status' => "error", 'main_error_message' => $ex->getMessage()]);
        }
    }

    public function update(Request $request) {


        try {
            $statusCode = 422;
            $return = [];
//           input_variable

            $title = $request->input('title');
            $id = $request->input('id');
            $level = $request->input('level');
            $announcement_data = $request->input('announcement_data');
            $employee_data = $request->input('employee_data');
            $notebook_data = $request->input('notebook_data');
            $roles_data = $request->input('roles_data');
            $report_data = $request->input('report_data');
            $setting_data = $request->input('setting_data');
            $role = Role::where('id', $id)->first();
            $role->title = $title;
            $role->level = $level;

            if ($role->save()) {

                $delete_old = RoleOption::where('role_id', $role->id)->delete();

                if ($delete_old) {
                    $option = new RoleOption;
                    $option->role_id = $role->id;
                    $option->option_name = "Announcement";
                    $option->add_edit = $announcement_data['add_edit'];
                    $option->view_download = $announcement_data['view_download'];
                    $option->approved_reject = $announcement_data['approved_reject'];
                    $option->delete_void = $announcement_data['delete_void'];
                    $option->save();

                    $option = new RoleOption;
                    $option->role_id = $role->id;
                    $option->option_name = "Employee";
                    $option->add_edit = $employee_data['add_edit'];
                    $option->view_download = $employee_data['view_download'];
                    $option->approved_reject = $employee_data['approved_reject'];
                    $option->delete_void = $employee_data['delete_void'];
                    $option->save();

                    $option = new RoleOption;
                    $option->role_id = $role->id;
                    $option->option_name = "Notebook";
                    $option->add_edit = $notebook_data['add_edit'];
                    $option->view_download = $notebook_data['view_download'];
                    $option->approved_reject = $notebook_data['approved_reject'];
                    $option->delete_void = $notebook_data['delete_void'];
                    $option->save();

                    $option = new RoleOption;
                    $option->role_id = $role->id;
                    $option->option_name = "Rolepermission";
                    $option->add_edit = $roles_data['add_edit'];
                    $option->view_download = $roles_data['view_download'];
                    $option->approved_reject = $roles_data['approved_reject'];
                    $option->delete_void = $roles_data['delete_void'];
                    $option->save();

                    $option = new RoleOption;
                    $option->role_id = $role->id;
                    $option->option_name = "Report";
                    $option->add_edit = $report_data['add_edit'];
                    $option->view_download = $report_data['view_download'];
                    $option->approved_reject = $report_data['approved_reject'];
                    $option->delete_void = $report_data['delete_void'];
                    $option->save();

                    $option = new RoleOption;
                    $option->role_id = $role->id;
                    $option->option_name = "Settings";
                    $option->add_edit = $setting_data['add_edit'];
                    $option->view_download = $setting_data['view_download'];
                    $option->approved_reject = $setting_data['approved_reject'];
                    $option->delete_void = $setting_data['delete_void'];
                    $option->save();
                }


                $return['data'] = $role;
                $return['options'] = RoleOption::where('role_id', $role->id)->get();
                $statusCode = 200;
                $return['status'] = 'success';
            }




            return $this->response($return, $statusCode);
        } catch (\Throwable $ex) {
            return $this->error(['status' => "error", 'main_error_message' => $ex->getMessage()]);
        }
    }

    public function show(Request $request) {
        try {
            $statusCode = 422;
            $return = [];
//           input_variable

            $id = $request->input('id');
            $role = Role::find($id);

            $return['announcement'] = RoleOption::where('role_id', $role->id)->where('option_name', 'Announcement')->first();
            $return['employee'] = RoleOption::where('role_id', $role->id)->where('option_name', 'Employee')->first();
            $return['notebook'] = RoleOption::where('role_id', $role->id)->where('option_name', 'Notebook')->first();
            $return['roles'] = RoleOption::where('role_id', $role->id)->where('option_name', 'Rolepermission')->first();
            $return['report'] = RoleOption::where('role_id', $role->id)->where('option_name', 'Report')->first();
            $return['setting'] = RoleOption::where('role_id', $role->id)->where('option_name', 'Settings')->first();

//            $role->role_options = RoleOption::where('role_id', $role->id)->get();
            $return['role'] = $role;
            $statusCode = 200;
            $return['status'] = 'success';

            return $this->response($return, $statusCode);
        } catch (\Throwable $ex) {
            return $this->error(['status' => "error", 'main_error_message' => $ex->getMessage()]);
        }
    }

}
