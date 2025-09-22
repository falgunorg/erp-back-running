<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Unit;
use Illuminate\Support\Facades\Validator;

class UnitController extends Controller {

    public function index(Request $request) {
        try {
            $statusCode = 422;
            $return = [];
            $user_id = $request->user->id;
            $units = Unit::orderBy('created_at', 'desc')->get();
            if ($units) {
                $return['data'] = $units;
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
            $user_id = $request->user->id;
            $validator = Validator::make($request->all(), [
                        'title' => 'required|unique:units',
            ]);
            if ($validator->fails()) {
                $return['errors'] = $validator->errors();
                $statusCode = 422;
            } else {
                $unit = new Unit;
                $unit->title = $request->input('title');
                $unit->user_id = $user_id;
                $unit->save();
                $return['data'] = $unit;
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

            $id = $request->input('id');
            $unit = Unit::find($id);

            if ($unit) {
                $return['data'] = $unit;
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
            $unit = Unit::find($id);

            $validator = Validator::make($request->all(), [
                        'title' => 'required|unique:units,title,' . $id,
            ]);
            if ($validator->fails()) {
                $return['errors'] = $validator->errors();
                $statusCode = 422;
            } else {
                $unit->title = $request->input('title');
                $unit->save();
                $return['data'] = $color;
                $statusCode = 200;
                $return['status'] = 'success';
            }



            return $this->response($return, $statusCode);
        } catch (\Throwable $ex) {
            return $this->error(['status' => "error", 'main_error_message' => $ex->getMessage()]);
        }
    }

}
