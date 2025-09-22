<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Company;

class CompanyController extends Controller {

    public function index(Request $request) {
        try {
            $statusCode = 422;
            $return = [];
            $type = $request->input('type');

            $query = Company::orderBy('created_at', 'desc');

            if ($type) {
                $query->where('type', $type);
            }

            $companies = $query->get();
            if ($companies) {
                $return['data'] = $companies;
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
            $address = $request->input('address');
            $company = new Company;

            if (strlen($title) == 0) {
                $return['errors']['title'] = 'Please insert title';
            } else {
                $company->title = $title;
            }
            if (strlen($address) == 0) {
                $return['errors']['address'] = 'Please insert address';
            } else {
                $company->address = $address;
            }

            if (!isset($return['errors'])) {
                if ($company->save()) {
                    $return['data'] = $company;
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
            $company = Company::find($id);

            if ($company) {
                $return['data'] = $company;
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
            $address = $request->input('address');
            $company = Company::find($id);

            if (strlen($title) == 0) {
                $return['errors']['title'] = 'Please insert title';
            } else {
                $company->title = $title;
            }
            if (strlen($address) == 0) {
                $return['errors']['address'] = 'Please insert title';
            } else {
                $company->address = $address;
            }

            if (!isset($return['errors'])) {
                if ($company->save()) {
                    $return['data'] = $company;
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
