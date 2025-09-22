<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\SubStoreAccess;
use Illuminate\Support\Facades\Validator;

class SubstoreAccessController extends Controller {

    public function index(Request $request) {
        try {
            $statusCode = 422;
            $return = [];
            $access = SubStoreAccess::orderBy('created_at', 'desc')->get();

            foreach ($access as $val) {
                $user = \App\Models\User::find($val->user_id);
                $val->user_name = $user->full_name;
                $department = \App\Models\Department::find($user->department);
                $val->department_title = $department->title;
                $designation = \App\Models\Designation::find($user->designation);
                $val->designation_title = $designation->title;
                $company = \App\Models\Company::find($user->company);
                $val->company_title = $company->title;
            }
            $return['data'] = $access;
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
                        'user_id' => 'required|unique:substore_access|max:11',
                        'area' => 'required',
            ]);

            if ($validator->fails()) {
                return response()->json([
                            'errors' => $validator->errors()
                                ], 422);
            }


            $user_id = $request->input('user_id');
            $area = $request->input('area');

            $access = new SubStoreAccess;
            $access->user_id = $user_id;
            $access->area = implode(',', $area);
            $access->save();

            $return['data'] = $access;
            $statusCode = 200;
            $return['status'] = 'success';

            return $this->response($return, $statusCode);
        } catch (\Throwable $ex) {
            return $this->error(['status' => "error", 'main_error_message' => $ex->getMessage()]);
        }
    }

    public function show(Request $request) {
        try {
            $statusCode = 422;
            $return = [];

            $id = $request->input('id');
            $access = SubStoreAccess::find($id);

            if ($access) {
                $return['data'] = $access;
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

    public function update(Request $request) {
        try {
            $statusCode = 422;
            $return = [];
            $id = $request->input('id');

            $validator = Validator::make($request->all(), [
                        'user_id' => 'required|max:11|unique:substore_access,user_id,' . $id,
                        'area' => 'required',
                        'id' => 'required',
            ]);

            if ($validator->fails()) {
                return response()->json([
                            'errors' => $validator->errors()
                                ], 422);
            }

            $access = SubStoreAccess::find($id);
            if (!$access) {
                return response()->json([
                            'errors' => 'Record not found'
                                ], 404);
            }

            $access->user_id = $request->input('user_id');
            $access->area = $request->input('area'); // This will be a comma-separated string
            $access->save();

            $return['data'] = $access;
            $statusCode = 200;
            $return['status'] = 'success';

            return response()->json($return, $statusCode);
        } catch (\Throwable $ex) {
            return response()->json(['status' => "error", 'main_error_message' => $ex->getMessage()], 500);
        }
    }

}
