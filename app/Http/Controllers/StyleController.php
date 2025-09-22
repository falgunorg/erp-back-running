<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Style;

class StyleController extends Controller {

    public function index(Request $request) {
        try {
            $statusCode = 422;
            $return = [];
            $buyer_id = $request->input('buyer_id');
            $contract_id = $request->input('contract_id');
            $query = Style::orderBy('created_at', 'desc');
            if ($contract_id) {
                $contract = \App\Models\PurchaseContract::where('id', $contract_id)->first();
                $query = $query->where('buyer_id', $contract->buyer_id);
            }
            if ($buyer_id) {
                $query = $query->where('buyer_id', $buyer_id);
            }
            $styles = $query->get();
            if ($styles) {
                $return['data'] = $styles;
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

            $title = $request->input('title');

            // Check for duplicate title 
            $existingStyle = Style::where('title', $title)->first();
            if ($existingStyle) {
                $return['errors']['title'] = 'Title already exists';
            } else {
                $style = new Style;

                if (strlen($title) == 0) {
                    $return['errors']['title'] = 'Please insert title';
                } else {
                    $style->title = $title;

                    if ($style->save()) {
                        $return['data'] = $style;
                        $statusCode = 200;
                        $return['status'] = 'success';
                    } else {
                        $return['errors']['main_error_message'] = 'Saving error';
                        $return['status'] = 'error';
                    }
                }
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
            $style = Style::find($id);

            if ($style) {
                $return['data'] = $style;
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
            $style = Style::find($id);
            if (strlen($title) == 0) {
                $return['errors']['title'] = 'Please insert title';
            } else {
                $style->title = $title;
            }

            if (!isset($return['errors'])) {
                if ($style->save()) {
                    $return['data'] = $style;
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
