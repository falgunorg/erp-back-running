<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Term;

class TermController extends Controller {

    public function index(Request $request) {
        try {
            $statusCode = 422;
            $return = [];

            $terms = Term::orderBy('created_at', 'desc')->get();
            if ($terms) {
                $return['data'] = $terms;
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
            $description = $request->input('description');

            $term = new Term;

            if (strlen($title) == 0) {
                $return['errors']['title'] = 'Please insert title';
            } else {
                $term->title = $title;
            }

            if (strlen($description) == 0) {
                $return['errors']['description'] = 'Please insert description';
            } else {
                $term->description = $description;
            }

            if (!isset($return['errors'])) {
                if ($term->save()) {
                    $return['data'] = $term;
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
            $term = Term::find($id);

            if ($term) {
                $return['data'] = $term;
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
            $description = $request->input('description');
            $id = $request->input('id');
            $term = Term::find($id);

            if (strlen($title) == 0) {
                $return['errors']['title'] = 'Please insert title';
            } else {
                $term->title = $title;
            }
            if (strlen($description) == 0) {
                $return['errors']['description'] = 'Please insert description';
            } else {
                $term->description = $description;
            }


            if (!isset($return['errors'])) {
                if ($term->save()) {
                    $return['data'] = $term;
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
