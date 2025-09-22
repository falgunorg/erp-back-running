<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Color;
use Illuminate\Support\Facades\Validator;

class ColorController extends Controller {

    public function index(Request $request) {
        try {
            $statusCode = 422;
            $return = [];
            $user_id = $request->user->id;
            $colors = Color::orderBy('created_at', 'desc')->get();
            if ($colors) {
                $return['data'] = $colors;
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
                        'title' => 'required|unique:colors',
            ]);

            if ($validator->fails()) {
                $return['errors'] = $validator->errors();
                $statusCode = 422;
            } else {
                $color = new Color;
                $color->title = $request->input('title');
                $color->user_id = $user_id;
                $color->save();
                $return['data'] = $color;
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
            $color = Color::find($id);

            if ($color) {
                $return['data'] = $color;
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
            $color = Color::find($id);

            $validator = Validator::make($request->all(), [
                        'title' => 'required|unique:colors,title,' . $id,
            ]);
            if ($validator->fails()) {
                $return['errors'] = $validator->errors();
                $statusCode = 422;
            } else {
                $color->title = $request->input('title');
                $color->save();
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
