<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Lc;
use App\Models\LcItem;
use Illuminate\Support\Facades\Validator;

class LcController extends Controller {

    public function admin_index(Request $request) {
        try {
            $statusCode = 422;
            $return = [];
            $supplier_id = $request->input('supplier_id');
            $status = $request->input('status');
            $from_date = $request->input('from_date');
            $to_date = $request->input('to_date');
            $num_of_row = $request->input('num_of_row');
            $company_id = $request->input('company_id');
            $query = Lc::orderBy('created_at', 'desc');
            // Apply filters
            if ($company_id) {
                $query->where('company_id', $company_id);
            }
            if ($status) {
                $query->where('status', $status);
            }
            if ($supplier_id) {
                $query->where('supplier_id', $supplier_id);
            }
            if ($from_date && $to_date) {
                $query->whereBetween('issued_date', [$from_date, $to_date]);
            }
            $lcs = $query->take($num_of_row)->get();

            if ($lcs) {
                foreach ($lcs as $val) {
                    $contract = \App\Models\PurchaseContract::where('id', $val->contract_id)->first();
                    $val->contract_number = $contract->title;
                    $supplier = \App\Models\Supplier::where('id', $val->supplier_id)->first();
                    $val->supplier = $supplier->company_name;
                    $bank = \App\Models\Bank::where('id', $val->bank)->first();
                    $val->bank_name = $bank->title;
                    $proformaArray = explode(',', $val->proformas);
                    $proformas = \App\Models\Proforma::whereIn('id', $proformaArray)->get();
                    $val->piList = $proformas;
                    $val->total_value = $proformas->sum('total');
                }
                $return['data'] = $lcs;
                $statusCode = 200;
                $return['status'] = 'success';
            } else {
                $return['status'] = 'error';
            }
            return $this->response($return, $statusCode);
        } catch (\Throwable $ex) {
            return $this->error(['status' => 'error', 'main_error_message' => $ex->getMessage()]);
        }
    }

    public function index(Request $request) {
        try {
            $statusCode = 422;
            $return = [];
            $user_id = $request->user->id;
            $supplier_id = $request->input('supplier_id');
            $status = $request->input('status');
            $from_date = $request->input('from_date');
            $to_date = $request->input('to_date');
            $num_of_row = $request->input('num_of_row');
            $query = Lc::where('user_id', $user_id)->orderBy('created_at', 'desc');

            // Apply filters
            if ($status) {
                $query->where('status', $status);
            }

            if ($supplier_id) {
                $query->where('supplier_id', $supplier_id);
            }
            if ($from_date && $to_date) {
                $query->whereBetween('issued_date', [$from_date, $to_date]);
            }
            $lcs = $query->take($num_of_row)->get();

            if ($lcs) {
                foreach ($lcs as $val) {
                    $contract = \App\Models\PurchaseContract::where('id', $val->contract_id)->first();
                    $val->contract_number = $contract->title;
                    $supplier = \App\Models\Supplier::where('id', $val->supplier_id)->first();
                    $val->supplier = $supplier->company_name;
                    $bank = \App\Models\Bank::where('id', $val->bank)->first();
                    $val->bank_name = $bank->title;
                    $proformaArray = explode(',', $val->proformas);
                    $proformas = \App\Models\Proforma::whereIn('id', $proformaArray)->get();
                    $val->piList = $proformas;
                    $val->total_value = $proformas->sum('total');
                }
                $return['data'] = $lcs;
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
                        'contract_id' => 'required',
                        'supplier_id' => 'required',
                        'proformas' => 'required',
                        'bank' => 'required',
                        'currency' => 'required',
                        'lc_number' => 'required|unique:lcs,lc_number',
                        'lc_validity' => 'nullable',
                        'apply_date' => 'nullable',
                        'issued_date' => 'nullable',
                        'maturity_date' => 'nullable',
                        'paid_date' => 'nullable',
                        'commodity' => 'nullable',
                        'pcc_avail' => 'nullable',
            ]);

            if ($validator->fails()) {
                return response()->json([
                            'errors' => $validator->errors()
                                ], 422);
            }
            // Create a new instance of the Lc model
            $lc = new Lc;
            $lc->user_id = $user_id;
            $lc->contract_id = $request->input('contract_id');
            $lc->supplier_id = $request->input('supplier_id');
            $lc->proformas = $request->input('proformas');
            $lc->bank = $request->input('bank');
            $lc->currency = $request->input('currency');
            $lc->lc_number = $request->input('lc_number');
            $lc->lc_validity = $request->input('lc_validity');
            $lc->apply_date = $request->input('apply_date');
            $lc->issued_date = $request->input('issued_date');
            $lc->maturity_date = $request->input('maturity_date');
            $lc->paid_date = $request->input('paid_date');
            $lc->commodity = $request->input('commodity');
            $lc->pcc_avail = $request->input('pcc_avail');

            if ($lc->save()) {
                $proformaArray = explode(',', $lc->proformas);
                $proformas = \App\Models\Proforma::whereIn('id', $proformaArray)->get();
                foreach ($proformas as $poroforma) {
                    $poroforma->status = 'BTB-Submitted';
                    $poroforma->save();

                    $notification = new \App\Models\Notification;
                    $notification->title = "Your PI Was Sumitted For LC";
                    $notification->receiver = $poroforma->user_id;
                    $notification->url = "/merchandising/proformas-details/" . $poroforma->id;
                    $notification->description = "Please Take Necessary Action";
                    $notification->is_read = 0;
                    $notification->save();
                }
            }
            $return['data'] = $lc;
            $statusCode = 200;
            $return['status'] = 'success';
            return response()->json($return, $statusCode);
        } catch (\Throwable $ex) {
            return $this->error(['status' => 'error', 'main_error_message' => $ex->getMessage()]);
        }
    }

    public function show(Request $request) {
        try {
            $statusCode = 422;
            $return = [];

            $id = $request->input('id');
            $lc = Lc::find($id);
            if ($lc) {
                $contract = \App\Models\PurchaseContract::where('id', $lc->contract_id)->first();
                $lc->contract_number = $contract->title;
                $lc->tag_number = $contract->tag_number;
                $buyer = \App\Models\Buyer::where('id', $contract->buyer_id)->first();
                $lc->buyer = $buyer->name;
                $company = \App\Models\Company::where('id', $contract->company_id)->first();
                $lc->company = $company->title;
                $lc->company_address = $company->address;
                $supplier = \App\Models\Supplier::where('id', $lc->supplier_id)->first();
                $lc->supplier = $supplier->company_name;
                $lc->supplier_address = $supplier->address;
                $lc->supplier_city = $supplier->state;
                $lc->supplier_country = $supplier->country;
                $lc->supplier_attention = $supplier->attention_person;
                $lc->supplier_contact = $supplier->mobile_number;

                $bank = \App\Models\Bank::where('id', $lc->bank)->first();
                $lc->bank_name = $bank->title;
                $lc->bank_branch = $bank->branch;
                $lc->bank_address = $bank->address;
                $lc->bank_country = $bank->country;

                $proformaArray = explode(',', $lc->proformas);
                $proformas = \App\Models\Proforma::whereIn('id', $proformaArray)->get();
                $lc->piList = $proformas;
                $lc->total_value = $proformas->sum('total');
                $return['data'] = $lc;
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
            $user_id = $request->user->id;
            $id = $request->input('id');

            $validator = Validator::make($request->all(), [
                        'contract_id' => 'required',
                        'supplier_id' => 'required',
                        'proformas' => 'required',
                        'bank' => 'required',
                        'currency' => 'required',
                        'lc_number' => 'required|unique:lcs,lc_number,' . $id,
                        'lc_validity' => 'nullable',
                        'apply_date' => 'nullable',
                        'issued_date' => 'nullable',
                        'maturity_date' => 'nullable',
                        'paid_date' => 'nullable',
                        'commodity' => 'nullable',
                        'pcc_avail' => 'nullable',
            ]);

            if ($validator->fails()) {
                return response()->json([
                            'errors' => $validator->errors()
                                ], 422);
            }
            // Create a new instance of the Lc model
            $lc = Lc::find($id);

            if (!$lc) {
                return response()->json(['status' => 'error', 'message' => 'LC not found'], 404);
            }
            $lc->user_id = $user_id;
            $lc->contract_id = $request->input('contract_id');
            $lc->supplier_id = $request->input('supplier_id');
            $lc->proformas = $request->input('proformas');
            $lc->bank = $request->input('bank');
            $lc->currency = $request->input('currency');
            $lc->lc_number = $request->input('lc_number');
            $lc->lc_validity = $request->input('lc_validity');
            $lc->apply_date = $request->input('apply_date');
            $lc->issued_date = $request->input('issued_date');
            $lc->maturity_date = $request->input('maturity_date');
            $lc->paid_date = $request->input('paid_date');
            $lc->commodity = $request->input('commodity');
            $lc->pcc_avail = $request->input('pcc_avail');
            $lc->save();
            $return['data'] = $lc;
            $statusCode = 200;
            $return['status'] = 'success';
            return response()->json($return, $statusCode);
        } catch (\Throwable $ex) {
            return $this->error(['status' => 'error', 'main_error_message' => $ex->getMessage()]);
        }
    }

}
