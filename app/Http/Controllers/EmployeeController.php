<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\User;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\DB;
use App\Models\Company;
use App\Models\Department;
use App\Models\Designation;
use App\Models\Role;
use App\Models\MailSign;

class EmployeeController extends Controller {

    public function index(Request $request) {
        try {
            $statusCode = 422;
            $return = [];
            $user = User::find($request->user->id);

            if ($request->input('perPage')) {
                $status = $request->input('status');
                $department = $request->input('department');
                $designation = $request->input('designation');
                $company = $request->input('company');
                $role = $request->input('role');
                $per_page = ($request->input('perPage') ? $request->input('perPage') : 12);
                $page_num = $request->input('page');
                $start = ($page_num - 1) * $per_page;

                $employees = new User();

                if ($status) {
                    $employees = $employees->where('status', $status);
                }
                if ($company) {
                    $employees = $employees->where('company', $company);
                }
                if ($department) {
                    $employees = $employees->where('department', $department);
                }
                if ($designation) {
                    $employees = $employees->where('designation', $designation);
                }
                if ($role) {
                    $employees = $employees->where('role_permission', $role);
                }

                $totalCount = $employees->count();
                $employees = $employees->skip($start);
                $employees = $employees->take($per_page);

                $data = $employees->orderBy('created_at', 'desc')
                        ->skip($start)
                        ->take($per_page)
                        ->get();

                foreach ($data as $val) {
                    $department = Department::where('id', $val->department)->first();
                    $designation = Designation::where('id', $val->designation)->first();
                    $company = Company::where('id', $val->company)->first();
                    $role = Role::where('id', $val->role_permission)->first();
                    $file_path = url('') . '/profile_pictures/' . $val->photo;
                    $val->profile_picture = $file_path;
                    $val->department_title = $department->title ? $department->title : "N/A";
                    $val->designation_title = $designation->title ? $designation->title : "N/A";
                    $val->company_title = $company->title ? $company->title : "N/A";
                    $val->role_title = $role->title ? $role->title : "N/A";
                }


                $return['data'] = $data;
                $return['totalCount'] = $totalCount;
                $page_count = ceil($totalCount / $per_page);
                $prevCount = (($page_num - 1) * $per_page) + count($data);
                $return['loadedCount'] = $prevCount;
                $return['paginationData'] = \App\Libraries\Paginator::paginateData($page_count, $page_num);
            } else {
                $users = User::orderBy('created_at', 'desc');
                $company_id = $request->input('company_id');
                $without_me = $request->input('without_me');
                $department = $request->input('department');

                $user = User::find($request->user->id);
                $recommended_group = $request->input('recommended_group');

                if ($recommended_group) {
                    $baseQuery = User::where('designation', 1)
                            ->where('department', 10);
                    $users = $baseQuery->orWhere(function ($query) use ($user) {
                        $query->where('designation', 1)
                                ->whereIn('department', [8, 21, 26, 28])
                                ->where('company', $user->company);
                    });
                }
                $issue_type = $request->input('issue_type');
                if ($issue_type) {
                    if ($issue_type === "Self") {
                        $users->where('company', $user->company)->where('status','Active');
                    } else if ($issue_type === "Sample") {
                        $users->where('designation', 8)->where('status','Active');
                    }
                }
                if ($company_id) {
                    $users = $users->where('company', $company_id);
                }
                if ($department) {
                    $users = $users->where('department', $department);
                }
                if ($without_me) {
                    $users = $users->where('id', '<>', $request->user->id);
                }
                $data = $users->get();
                foreach ($data as $val) {
                    $department = Department::where('id', $val->department)->first();
                    $designation = Designation::where('id', $val->designation)->first();
                    $company = Company::where('id', $val->company)->first();
                    $role = Role::where('id', $val->role_permission)->first();
                    $val->department_title = $department->title ? $department->title : "N/A";
                    $val->designation_title = $designation->title ? $designation->title : "N/A";
                    $val->company_title = $company->title ? $company->title : "N/A";
                    $val->role_title = $role->title ? $role->title : "N/A";
                    $file_path = url('') . '/profile_pictures/' . $val->photo;
                    $val->profile_picture = $file_path;
                }
                $return['data'] = $data;
            }
            $statusCode = 200;
            return $this->response($return, $statusCode);
        } catch (\Throwable $ex) {
            return $this->error(['status' => "error", 'main_error_message' => $ex->getMessage(), 'AT LINE' => $ex->getLine()]);
        }
    }

    public function store(Request $request) {

        try {
            $statusCode = 422;
            $return = [];

            $validator = Validator::make($request->all(), [
                        'full_name' => 'required|string|max:255',
                        'email' => 'required|string|unique:users|max:255',
                        'password' => 'required|string|min:6',
                        'staff_id' => 'required|string|unique:users|max:255',
                        'role_permission' => 'required',
                        'department' => 'required',
                        'designation' => 'required',
                        'company' => 'required',
                        'status' => 'required',
            ]);

            if ($validator->fails()) {
                return response()->json([
                            'errors' => $validator->errors()
                                ], 422);
            }


            $user = new User([
                'full_name' => $request->input('full_name'),
                'email' => $request->input('email'),
                'password' => \App\Libraries\Tokenizer::password($request->input('password')),
                'staff_id' => $request->input('staff_id'),
                'role_permission' => $request->input('role_permission'),
                'department' => $request->input('department'),
                'designation' => $request->input('designation'),
                'company' => $request->input('company'),
                'status' => $request->input('status'),
            ]);

            if (isset($_FILES['photo']['name'])) {

                $public_path = public_path();
                $path = $public_path . '/' . "profile_pictures";
                $pathinfo = pathinfo($_FILES['photo']['name']);

                $basename = strtolower(str_replace(' ', '_', $pathinfo['filename']));
                $extension = strtolower($pathinfo['extension']);

                $file_name = $basename . '.' . $extension;

                $finalpath = $path . '/' . $file_name;
                if (file_exists($finalpath)) {
                    $file_name = $basename . time() . '.' . $extension;
                    $finalpath = $path . '/' . $file_name;
                }
                if (move_uploaded_file($_FILES['photo']['tmp_name'], $finalpath)) {
                    $user->photo = $file_name;
                }
            }
            $user->save();

            $statusCode = 200;
            $return['data'] = $user;
            $return['status'] = 'success';

            return $this->response($return, $statusCode);
        } catch (\Throwable $ex) {

            return $this->error(['status' => "error", 'main_error_message' => $ex->getMessage(), 'AT LINE' => $ex->getLine()]);
        }
    }

    public function show(Request $request) {
        try {
            $statusCode = 422;
            $return = [];
            $id = $request->input('id');
            $employee = User::find($id);
            $mail_sign = MailSign::where('user_id', $id)->first();
            if ($mail_sign) {
                $employee->mailSign = $mail_sign->description;
            } else {
                $employee->mailSign = 'N/A';
            }
            $file_path = url('') . '/profile_pictures/' . $employee->photo;
            $employee->profile_picture = $file_path;
            $statusCode = 200;
            $return['data'] = $employee;
            $return['status'] = 'success';
            return $this->response($return, $statusCode);
        } catch (\Throwable $ex) {
            return $this->error(['status' => "error", 'main_error_message' => $ex->getMessage()]);
        }
    }

    public function update(Request $request) {
        try {

            $id = $request->input('id');
            $statusCode = 422;
            $return = [];

            $validator = Validator::make($request->all(), [
                        'full_name' => 'required|string|max:255',
                        'email' => 'required|string|max:255|unique:users,email,' . $id,
                        'staff_id' => 'required|string|max:255|unique:users,staff_id,' . $id,
                        'role_permission' => 'required',
                        'department' => 'required',
                        'designation' => 'required',
                        'company' => 'required',
                        'status' => 'required',
                        'mailSign' => 'nullable',
            ]);

            if ($validator->fails()) {
                return response()->json([
                            'errors' => $validator->errors()
                                ], 422);
            }

            $user = User::findOrFail($id);

            $user->fill([
                'full_name' => $request->input('full_name'),
                'email' => $request->input('email'),
                'staff_id' => $request->input('staff_id'),
                'role_permission' => $request->input('role_permission'),
                'department' => $request->input('department'),
                'designation' => $request->input('designation'),
                'company' => $request->input('company'),
                'status' => $request->input('status'),
            ]);

            if (isset($_FILES['photo']['name'])) {

                $public_path = public_path();
                $path = $public_path . '/' . "profile_pictures";
                $pathinfo = pathinfo($_FILES['photo']['name']);

                $basename = strtolower(str_replace(' ', '_', $pathinfo['filename']));
                $extension = strtolower($pathinfo['extension']);

                $file_name = $basename . '.' . $extension;

                $finalpath = $path . '/' . $file_name;
                if (file_exists($finalpath)) {
                    $file_name = $basename . time() . '.' . $extension;
                    $finalpath = $path . '/' . $file_name;
                }
                if (move_uploaded_file($_FILES['photo']['tmp_name'], $finalpath)) {
                    $user->photo = $file_name;
                }
            }

            if ($user->save()) {
                $exist = MailSign::where('user_id', $user->id)->first();
                if ($exist) {
                    $exist->description = $request->input('mailSign');
                    $exist->save();
                } else {
                    $create_sign = new MailSign;
                    $create_sign->user_id = $user->id;
                    $create_sign->description = $request->input('mailSign');
                    $create_sign->save();
                }

                $statusCode = 200;
                $return['data'] = $user;
                $return['status'] = 'success';
            }





            return $this->response($return, $statusCode);
        } catch (\Throwable $ex) {
            return $this->error(['status' => "error", 'main_error_message' => $ex->getMessage()]);
        }
    }

    public function change_password(Request $request) {
        try {

            $id = $request->input('id');
            $password = $request->input('password');
            $statusCode = 422;
            $return = [];
            $validator = Validator::make($request->all(), [
                        'id' => 'required',
                        'password' => 'required|min:6',
            ]);

            if ($validator->fails()) {
                return response()->json([
                            'errors' => $validator->errors()
                                ], 422);
            }

            $user = User::findOrFail($id);
            if ($password) {
                $user->password = \App\Libraries\Tokenizer::password($password);
            }
            $user->save();
            $statusCode = 200;
            $return['data'] = $user;
            $return['status'] = 'success';
            return $this->response($return, $statusCode);
        } catch (\Throwable $ex) {
            return $this->error(['status' => "error", 'main_error_message' => $ex->getMessage()]);
        }
    }

    public function destroy(Request $request) {
        return "Deleted Success";
    }

}
