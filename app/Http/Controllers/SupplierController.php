<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Supplier;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\DB;

class SupplierController extends Controller {

    public function index(Request $request) {
        try {
            $statusCode = 422;
            $return = [];
            $status = $request->input('status');
            $country = $request->input('country');

            $query = Supplier::orderBy('company_name', 'asc');

            // Apply filters
            if ($status) {
                $query->where('status', $status);
            }

            if ($country) {
                $query->where('country', $country);
            }

            $suppliers = $query->get();

            foreach ($suppliers as $val) {
                $added_by = \App\Models\User::where('id', $val->added_by)->first();
                $val->added_by_name = $added_by->full_name;
            }



            $return['data'] = $suppliers;
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


            $validator = Validator::make($request->all(), [
                        'company_name' => 'required|string|min:3',
                        'email' => 'required|string|unique:suppliers|max:255',
                        'attention_person' => 'required|string|min:3',
                        'mobile_number' => 'required|min:5',
                        'country' => 'required',
                        'status' => 'required',
            ]);

            if ($validator->fails()) {
                return response()->json([
                            'errors' => $validator->errors()
                                ], 422);
            }

            $supplier = new Supplier([
                'company_name' => $request->input('company_name'),
                'email' => $request->input('email'),
                'attention_person' => $request->input('attention_person'),
                'mobile_number' => $request->input('mobile_number'),
                'country' => $request->input('country'),
                'status' => $request->input('status'),
                'address' => $request->input('address'),
                'office_number' => $request->input('office_number'),
                'state' => $request->input('state'),
                'postal_code' => $request->input('postal_code'),
                'vat_reg_number' => $request->input('vat_reg_number'),
                'product_supply' => $request->input('product_supply'),
                'added_by' => $request->user->id,
            ]);

            $supplier->save();
            $statusCode = 200;
            $return['data'] = $supplier;
            $return['status'] = 'success';

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
            $supplier = Supplier::find($id);

            if ($supplier) {
                $return['data'] = $supplier;
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

            // Validate the input
            $validator = Validator::make($request->all(), [
                        'company_name' => 'required|string|min:3',
                        'email' => 'required|string|max:255|unique:suppliers,email,' . $id,
                        'attention_person' => 'required|string|min:3',
                        'mobile_number' => 'required|min:5',
                        'country' => 'required',
                        'status' => 'required',
            ]);

            if ($validator->fails()) {
                return response()->json([
                            'errors' => $validator->errors()
                                ], 422);
            }

            // Find the supplier
            $supplier = Supplier::findOrFail($id);

            // Update the supplier's information
            $supplier->update([
                'company_name' => $request->input('company_name'),
                'email' => $request->input('email'),
                'attention_person' => $request->input('attention_person'),
                'mobile_number' => $request->input('mobile_number'),
                'country' => $request->input('country'),
                'status' => $request->input('status'),
                'address' => $request->input('address'),
                'office_number' => $request->input('office_number'),
                'state' => $request->input('state'),
                'postal_code' => $request->input('postal_code'),
                'vat_reg_number' => $request->input('vat_reg_number'),
                'product_supply' => $request->input('product_supply'),
            ]);

            $statusCode = 200;
            $return['data'] = $supplier;
            $return['status'] = 'success';

            return $this->response($return, $statusCode);
        } catch (\Throwable $ex) {
            return $this->error(['status' => "error", 'main_error_message' => $ex->getMessage()]);
        }
    }

}
