<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Team;
use App\Models\User;
use Illuminate\Support\Facades\Validator;

class TeamController extends Controller {

    public function index(Request $request) {
        try {
            $statusCode = 422;
            $return = [];
            $status = $request->input('status');
            $from_date = $request->input('from_date');
            $to_date = $request->input('to_date');
            $num_of_row = $request->input('num_of_row');
            //get all teams   
            $all_teams = Team::all();
            // Query builder instance
            $query = Team::orderBy('created_at', 'desc');

            // Apply filters
            if ($status) {
                $query->where('status', $status);
            }
            if ($from_date && $to_date) {
                $query->whereBetween('created_at', [$from_date, $to_date]);
            }
            $teams = $query->take($num_of_row)->get();

            foreach ($teams as &$val) {
                $user = User::where('id', $val->user_id)->first();
                $val->user_photo = url('') . '/profile_pictures/' . $user->photo;
                $val->user_name = $user->full_name;
                $team_leader = User::where('id', $val->team_lead)->first();
                $val->team_leader_username = $team_leader->full_name;
                $val->team_leader_photo = url('') . '/profile_pictures/' . $team_leader->photo;
                $comapny = \App\Models\Company::where('id', $val->company_id)->first();
                $val->company_title = $comapny->title;
                $department = \App\Models\Department::where('id', $val->department)->first();
                $val->department_title = $department->title;

                $employees = explode(',', $val->employees);
                $employee_list = [];
                foreach ($employees as $key => &$val2) {
                    $employee = User::where('id', $val2)->first();
                    $employee_list[$key]['employee_name'] = $employee->full_name;
                    $employee_list[$key]['employee_photo'] = url('') . '/profile_pictures/' . $employee->photo;
                }
                $val->employee_list = $employee_list;
            }
            $return['data'] = $teams;
            $return['all_teams'] = $all_teams;

            $statusCode = 200;

            return $this->response($return, $statusCode);
        } catch (\Throwable $ex) {
            return $this->error(['status' => 'error', 'main_error_message' => $ex->getMessage()]);
        }
    }

    public function store(Request $request) {
        try {
            $statusCode = 422;
            $return = [];
            $validator = Validator::make($request->all(), [
                        'team_number' => 'required|unique:teams|max:255',
                        'title' => 'required|unique:teams|max:255',
                        'team_lead' => 'required',
                        'employees' => 'required',
                        'department' => 'required',
                        'company_id' => 'required',
            ]);

            if ($validator->fails()) {
                return response()->json([
                            'errors' => $validator->errors()
                                ], 422);
            }

            $team = new Team([
                'team_number' => $request->input('team_number'),
                'title' => $request->input('title'),
                'team_lead' => $request->input('team_lead'),
                'employees' => $request->input('employees'),
                'department' => $request->input('department'),
                'company_id' => $request->input('company_id'),
                'description' => $request->input('description'),
                'user_id' => $request->user->id,
            ]);

            $team->save();
            $statusCode = 200;
            $return['data'] = $team;
            $return['status'] = 'success';
            return $this->response($return, $statusCode);
        } catch (\Throwable $ex) {
            return $this->error(['status' => "error", 'main_error_message' => $ex->getMessage()]);
        }
    }

    public function show(Request $request) {
        $user_id = $request->user->id;
        try {

            $statusCode = 422;
            $return = [];
            $id = $request->input('id');
            $team = Team::where('id', $id)->first();

            if ($team) {
                $user = User::where('id', $team->user_id)->first();
                $team->user_photo = url('') . '/profile_pictures/' . $user->photo;
                $team->user_name = $user->full_name;
                $team_leader = User::where('id', $team->team_lead)->first();

                $team_leader_department = \App\Models\Department::where('id', $team_leader->department)->first();
                $team->team_leader_department = $team_leader_department->title;
                $team_leader_designation = \App\Models\Designation::where('id', $team_leader->designation)->first();
                $team->team_leader_designation = $team_leader_designation->title;
                $team->team_leader_username = $team_leader->full_name;
                $team->team_leader_photo = url('') . '/profile_pictures/' . $team_leader->photo;
                $comapny = \App\Models\Company::where('id', $team->company_id)->first();
                $team->company_title = $comapny->title;
                $department = \App\Models\Department::where('id', $team->department)->first();
                $team->department_title = $department->title;
                $employees = explode(',', $team->employees);
                $employee_list = [];

                $employeeArray = explode(',', $team->employees);
                $employees_array = \App\Models\User::whereIn('id', $employeeArray)->get();
                $team->employees_array = $employees_array;

                foreach ($employees as $key => &$val2) {
                    $employee = User::where('id', $val2)->first();
                    $employee_list[$key]['id'] = $employee->id;
                    $employee_list[$key]['full_name'] = $employee->full_name;
                    $employee_list[$key]['employee_photo'] = url('') . '/profile_pictures/' . $employee->photo;
                    $employee_department = \App\Models\Department::where('id', $employee->department)->first();
                    $employee_list[$key]['employee_department'] = $employee_department->title;
                    $employee_designation = \App\Models\Designation::where('id', $employee->designation)->first();
                    $employee_list[$key]['employee_designation'] = $employee_designation->title;
                }
                $team->employee_list = $employee_list;
                $team->employees = $employees;
                $return['data'] = $team;
                $statusCode = 200;
                $return['status'] = 'success';
            } else {
                $return['status'] = 'error';
            }
            return $this->response($return, $statusCode);
        } catch (\Throwable $ex) {
            return $this->error(['status' => "error", 'main_error_message' => $ex->getMessage()]);
        }
    }

    public function get_teams_with_user(Request $request) {

        try {
            $statusCode = 422;
            $return = [];

            $user_id = $request->user->id;

            $teams = Team::where('employees', 'like', '%' . $user_id . '%')
                    ->orWhere('team_lead', 'like', '%' . $user_id . '%')
                    ->get();
            $return['data'] = $teams;
            $statusCode = 200;
            $return['status'] = 'success';
            return $this->response($return, $statusCode);
        } catch (\Throwable $ex) {
            return $this->error(['status' => "error", 'main_error_message' => $ex->getMessage()]);
        }
    }

    public function update(Request $request) {
        try {
            $statusCode = 422;
            $return = [];

            $id = $request->input('id');

            $validator = Validator::make($request->all(), [
                        'team_number' => 'required|unique:teams,team_number,' . $id,
                        'title' => 'required|unique:teams,title,' . $id,
                        'team_lead' => 'required',
                        'employees' => 'required',
                        'department' => 'required',
                        'company_id' => 'required',
            ]);

            if ($validator->fails()) {
                return response()->json([
                            'errors' => $validator->errors()
                                ], 422);
            }

            $team = Team::find($id);

            if (!$team) {
                return $this->error(['status' => 'error', 'main_error_message' => 'Team not found.']);
            }

            $team->team_number = $request->input('team_number');
            $team->title = $request->input('title');
            $team->team_lead = $request->input('team_lead');
            $team->employees = $request->input('employees');
            $team->department = $request->input('department');
            $team->company_id = $request->input('company_id');
            $team->description = $request->input('description');
            $team->user_id = $request->user->id;

            $team->save();

            $statusCode = 200;
            $return['data'] = $team;
            $return['status'] = 'success';

            return $this->response($return, $statusCode);
        } catch (\Throwable $ex) {
            return $this->error(['status' => 'error', 'main_error_message' => $ex->getMessage()]);
        }
    }

    public function destroy(Request $request) {
        return "Deleted Success";
    }

}
