<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Buyer;

class BuyerController extends Controller {

    public function index(Request $request) {
        try {
            $statusCode = 422;
            $return = [];
            $status = $request->input('status');
            $country = $request->input('country');

            // Query builder instance
            $query = Buyer::orderBy('created_at', 'desc');

            // Apply filters
            if ($status) {
                $query->where('status', $status);
            }

            if ($country) {
                $query->where('country', $country);
            }

            $buyers = $query->get();

            $return['data'] = $buyers;
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
//           input_variable

            $name = $request->input('name');
            $country = $request->input('country');
            $address = $request->input('address');
            $status = $request->input('status');

            $buyer = new Buyer;

            if (strlen($name) == 0) {
                $return['errors']['name'] = 'Please insert name';
            } else {
                $buyer->name = $name;
            }
            if (strlen($country) == 0) {
                $return['errors']['country'] = 'Please insert country';
            } else {
                $buyer->country = $country;
            }

            if (strlen($status) == 0) {
                $return['errors']['status'] = 'Please insert status';
            } else {
                $buyer->status = $status;
            }
            $buyer->address = $address;

            if (!isset($return['errors'])) {
                if ($buyer->save()) {
                    $return['data'] = $buyer;
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
            $buyer = Buyer::find($id);

            if ($buyer) {
                $return['data'] = $buyer;
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

            $name = $request->input('name');
            $country = $request->input('country');
            $address = $request->input('address');
            $status = $request->input('status');
            $id = $request->input('id');
            $buyer = Buyer::find($id);

            if (strlen($name) == 0) {
                $return['errors']['name'] = 'Please insert name';
            } else {
                $buyer->name = $name;
            }
            if (strlen($country) == 0) {
                $return['errors']['country'] = 'Please insert country';
            } else {
                $buyer->country = $country;
            }

            if (strlen($status) == 0) {
                $return['errors']['status'] = 'Please insert status';
            } else {
                $buyer->status = $status;
            }
            $buyer->address = $address;

            if (!isset($return['errors'])) {
                if ($buyer->save()) {
                    $return['data'] = $buyer;
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
