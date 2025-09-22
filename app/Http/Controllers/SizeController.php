<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Size;
use Illuminate\Support\Facades\Validator;

class SizeController extends Controller {

    public function index(Request $request) {
        try {
            $statusCode = 422;
            $return = [];
            $user_id = $request->user->id;
            $sizes = Size::orderBy('created_at', 'desc')->get();
            if ($sizes) {
                $return['data'] = $sizes;
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
            $user_id = $request->user->id;

            $validator = Validator::make($request->all(), [
                        'title' => 'required|unique:sizes',
            ]);

            if ($validator->fails()) {
                $return['errors'] = $validator->errors();
                $statusCode = 422;
            } else {
                $size = new Size;
                $size->title = $request->input('title');
                $size->user_id = $user_id;
                $size->save();
                $return['data'] = $size;
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
            $size = Size::find($id);

            if ($size) {
                $return['data'] = $size;
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
            $size = Size::find($id);

            $validator = Validator::make($request->all(), [
                        'title' => 'required|unique:sizes,title,' . $id,
            ]);
            if ($validator->fails()) {
                $return['errors'] = $validator->errors();
                $statusCode = 422;
            } else {
                $size->title = $request->input('title');
                $size->save();
                $return['data'] = $size;
                $statusCode = 200;
                $return['status'] = 'success';
            }

            return $this->response($return, $statusCode);
        } catch (\Throwable $ex) {
            return $this->error(['status' => "error", 'main_error_message' => $ex->getMessage()]);
        }
    }

}
