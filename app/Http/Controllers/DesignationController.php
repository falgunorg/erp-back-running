<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Designation;

class DesignationController extends Controller {

    public function index(Request $request) {
        try {
            $statusCode = 422;
            $return = [];

            $designations = Designation::orderBy('created_at', 'desc')->get();
            if ($designations) {
                $return['data'] = $designations;
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

            $designation = new Designation;

            if (strlen($title) == 0) {
                $return['errors']['title'] = 'Please insert title';
            } else {
                $designation->title = $title;
            }

            if (!isset($return['errors'])) {
                if ($designation->save()) {
                    $return['data'] = $designation;
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
            $designation = Designation::find($id);

            if ($designation) {
                $return['data'] = $designation;
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
            $designation = Designation::find($id);

            if (strlen($title) == 0) {
                $return['errors']['title'] = 'Please insert title';
            } else {
                $designation->title = $title;
            }


            if (!isset($return['errors'])) {
                if ($designation->save()) {
                    $return['data'] = $designation;
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
