<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\SampleType;

class SampleTypeController extends Controller {
    public function index(Request $request) {
        try {
            $statusCode = 422;
            $return = [];
            $from_date = $request->input('from_date');
            $to_date = $request->input('to_date');
            $buyer_id = $request->input('buyer_id');
            $num_of_row = $request->input('num_of_row');

            $query = SampleType::orderBy('created_at', 'desc');

            if ($from_date && $to_date) {
                $query->whereBetween('created_at', [$from_date, $to_date]);
            }
            if ($buyer_id) {
                $query->where('buyer_id', $buyer_id);
            }
            // Limit the result to "num_of_row" records
            $sample_types = $query->take($num_of_row)->get();
            if ($sample_types) {
                foreach ($sample_types as $val) {
                    $val->buyer = \App\Models\Buyer::where('id', $val->buyer_id)->first();
                    $val->user = \App\Models\User::where('id', $val->user_id)->first();
                }
                $return['data'] = $sample_types;
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
            $user_id = $request->user->id;
            $buyer_id = $request->input('buyer_id');

            $sample_type = new SampleType;

            if (strlen($title) == 0) {
                $return['errors']['title'] = 'Please insert title';
            } else {
                $sample_type->title = $title;
            }
            if (strlen($buyer_id) == 0) {
                $return['errors']['buyer_id'] = 'Please select buyer';
            } else {
                $sample_type->buyer_id = $buyer_id;
            }

            $sample_type->user_id = $user_id;

            if (!isset($return['errors'])) {
                if ($sample_type->save()) {
                    $return['data'] = $sample_type;
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
            $sample_type = SampleType::find($id);

            if ($sample_type) {
                $return['data'] = $sample_type;
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
            $title = $request->input('title');
            $id = $request->input('id');
            $buyer_id = $request->input('buyer_id');

            $sample_type = SampleType::find($id);

            if (strlen($title) == 0) {
                $return['errors']['title'] = 'Please insert title';
            } else {
                $sample_type->title = $title;
            }
            if (strlen($buyer_id) == 0) {
                $return['errors']['buyer_id'] = 'Please select buyer';
            } else {
                $sample_type->buyer_id = $buyer_id;
            }


            if (!isset($return['errors'])) {
                if ($sample_type->save()) {
                    $return['data'] = $sample_type;
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
