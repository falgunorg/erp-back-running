<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Department;

class DepartmentController extends Controller {

    public function index(Request $request) {
        try {
            $statusCode = 422;
            $return = [];

            $departments = Department::orderBy('created_at', 'desc')->get();
            if ($departments) {
                $return['data'] = $departments;
                $statusCode = 200;
            } else {
                $return['status'] = 'error';
            }
            return $this->response($return, $statusCode);
        } catch (\Throwable $ex) {
            return $this->error(['status' => 'error', 'main_error_message' => $ex->getMessage()]);
        }
    }

    public function store(Request $request) {


        try {
            $statusCode = 422;
            $return = [];
//           input_variable

            $title = $request->input('title');

            $department = new \App\Models\Department();

            if (strlen($title) == 0) {
                $return['errors']['title'] = 'Please insert title';
            } else {
                $department->title = $title;
            }

            if (!isset($return['errors'])) {
                if ($department->save()) {
                    $return['data'] = $department;
                    $statusCode = 200;
                    $return['status'] = 'success';
                } else {
                    $return['errors']['main_error_message'] = 'Saving error';
                    $return['status'] = 'error';
                }
            } else {
                $return['status'] = 'error';
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

            $id = $request->input('id');
            $department = Department::find($id);

            if ($department) {
                $return['data'] = $department;
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
//           input_variable

            $title = $request->input('title');
            $id = $request->input('id');
            $department = Department::find($id);

            if (strlen($title) == 0) {
                $return['errors']['title'] = 'Please insert title';
            } else {
                $department->title = $title;
            }


            if (!isset($return['errors'])) {
                if ($department->save()) {
                    $return['data'] = $department;
                    $statusCode = 200;
                    $return['status'] = 'success';
                } else {
                    $return['errors']['main_error_message'] = 'Saving error';
                    $return['status'] = 'error';
                }
            } else {
                $return['status'] = 'error';
            }
            return $this->response($return, $statusCode);
        } catch (\Throwable $ex) {
            return $this->error(['status' => "error", 'main_error_message' => $ex->getMessage()]);
        }
    }

}
