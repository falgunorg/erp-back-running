<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Issue;
use Illuminate\Support\Facades\Validator;
use App\Models\Booking;

class IssueController extends Controller {

    public function admin_index(Request $request) {
        try {
            $statusCode = 422;
            $return = [];
            $from_date = $request->input('from_date');
            $to_date = $request->input('to_date');
            $num_of_row = $request->input('num_of_row');
            $filter_items = $request->input('filter_items');
            $buyer_id = $request->input('buyer_id');
            $booking_id = $request->input('booking_id');
            $supplier_id = $request->input('supplier_id');
            $company_id = $request->input('company_id');
            $techpack_id = $request->input('techpack_id');
            $issue_type = $request->input('issue_type');
            $query = Issue::orderBy('created_at', 'desc');
            if ($company_id) {
                $query->where('company_id', $company_id);
            }
            if ($from_date && $to_date) {
                $query->whereBetween('created_at', [$from_date, $to_date]);
            }
            if ($techpack_id) {
                $query->where('techpack_id', $techpack_id);
            }
            if ($filter_items) {
                $query->whereIn('id', $filter_items);
            }
            if ($buyer_id) {
                $query->where('buyer_id', $buyer_id);
            }
            if ($supplier_id) {
                $query->where('supplier_id', $supplier_id);
            }
            if ($booking_id) {
                $query->where('booking_id', $booking_id);
            }
            if ($issue_type) {
                $query->where('issue_type', $issue_type);
            }
            // Limit the result to "num_of_row" records
            $issues = $query->take($num_of_row)->get();
            // Retrieve all data without additional filters
            foreach ($issues as $val) {
                $val->supplier = optional(\App\Models\Supplier::find($val->supplier_id))->company_name;
                $val->buyer = optional(\App\Models\Buyer::find($val->buyer_id))->name;
                $budget = \App\Models\Budget::find($val->budget_id);
                $val->buyer_id = optional($budget)->buyer_id;
                $booking = Booking::find($val->booking_id);
                $val->booking_number = $booking->booking_number;
                $booked_by = \App\Models\User::find($val->booking_user_id);
                $val->booked_by = $booked_by->full_name;
                $issue_by = \App\Models\User::find($val->user_id);
                $val->issue_by = $issue_by->full_name;
                $val->supplier_id = optional($booking)->supplier_id;
                $val->techpack = optional(\App\Models\Techpack::find($val->techpack_id))->title;
                $company = \App\Models\Company::where('id', $val->company_id)->first();
                $val->company_name = $company->title;
                $budget_item = \App\Models\BudgetItem::find($val->budget_item_id);
                $item = \App\Models\Item::find(optional($budget_item)->item_id);
                $val->item_name = optional($item)->title;
                $val->image_source = url('') . '/booking_items/' . $val->photo;
                $val->challan_file = url('') . '/challan-copies/' . $val->challan_copy;
                $val->issue_company_name = optional(\App\Models\Company::find($val->issuing_company))->title;
                $issue_to = \App\Models\User::find($val->issue_to);
                if ($issue_to) {
                    $val->issue_to_user = $issue_to->full_name;
                } else {
                    $val->issue_to_user = "N/A";
                }
            }
            $return['data'] = $issues;
            $statusCode = 200;
            $return['status'] = 'success';

            return $this->response($return, $statusCode);
        } catch (\Throwable $ex) {
            return $this->error(['status' => 'error', 'main_error_message' => $ex->getMessage()]);
        }
    }

    public function index(Request $request) {
        try {
            $statusCode = 422;
            $return = [];

            $user = \App\Models\User::find($request->user->id);
            $from_date = $request->input('from_date');
            $to_date = $request->input('to_date');
            $num_of_row = $request->input('num_of_row');
            $filter_items = $request->input('filter_items');
            $buyer_id = $request->input('buyer_id');
            $booking_id = $request->input('booking_id');
            $supplier_id = $request->input('supplier_id');
            $techpack_id = $request->input('techpack_id');
            $issue_type = $request->input('issue_type');

            $query = Issue::where('company_id', $user->company)->orderBy('created_at', 'desc');
            // Apply the date range filter if both "from_date" and "to_date" are provided
            if ($from_date && $to_date) {
                $query->whereBetween('created_at', [$from_date, $to_date]);
            }
            if ($techpack_id) {
                $query->where('techpack_id', $techpack_id);
            }
            if ($filter_items) {
                $query->whereIn('id', $filter_items);
            }
            if ($buyer_id) {
                $query->where('buyer_id', $buyer_id);
            }
            if ($supplier_id) {
                $query->where('supplier_id', $supplier_id);
            }
            if ($booking_id) {
                $query->where('booking_id', $booking_id);
            }
            if ($issue_type) {
                $query->where('issue_type', $issue_type);
            }

            // Limit the result to "num_of_row" records
            $issues = $query->take($num_of_row)->get();

            // Retrieve all data without additional filters
            $allData = Issue::orderBy('created_at', 'desc')->get();
            foreach ($issues as $val) {
                $val->supplier = optional(\App\Models\Supplier::find($val->supplier_id))->company_name;
                $val->buyer = optional(\App\Models\Buyer::find($val->buyer_id))->name;
                $budget = \App\Models\Budget::find($val->budget_id);
                $val->buyer_id = optional($val)->buyer_id;
                $booking = Booking::find($val->booking_id);
                $val->booking_number = $booking->booking_number;
                $booked_by = \App\Models\User::find($val->booking_user_id);
                $val->booked_by = $booked_by->full_name;
                $issue_by = \App\Models\User::find($val->user_id);
                $val->issue_by = $issue_by->full_name;
                $val->supplier_id = optional($booking)->supplier_id;
                $val->techpack = optional(\App\Models\Techpack::find($val->techpack_id))->title;
                $val->issue_company_name = optional(\App\Models\Company::find($val->issuing_company))->title;
                $issue_to = \App\Models\User::find($val->issue_to);
                if ($issue_to) {
                    $val->issue_to_user = $issue_to->full_name;
                } else {
                    $val->issue_to_user = "N/A";
                }

                $budget_item = \App\Models\BudgetItem::find($val->budget_item_id);
                $item = \App\Models\Item::find(optional($budget_item)->item_id);
                $val->item_name = optional($item)->title;
                $val->image_source = url('') . '/booking_items/' . $val->photo;
                $val->challan_file = url('') . '/challan-copies/' . $val->challan_copy;
            }
            $return['data'] = $issues;
            $return['allData'] = $allData;
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
            $user_id = $request->user->id;
            $validator = Validator::make($request->all(), [
                        'id' => 'required',
                        'booking_item_id' => 'required',
                        'issue_type' => 'required',
                        'reference' => 'required',
                        'booking_user_id' => 'required',
                        'supplier_id' => 'required',
                        'buyer_id' => 'required',
                        'company_id' => 'required',
                        'booking_id' => 'required',
                        'budget_id' => 'required',
                        'budget_item_id' => 'required',
                        'techpack_id' => 'required',
                        'challan_no' => 'required',
                        'gate_pass' => 'required',
                        'issue_qty' => 'required',
                        'qty' => 'required',
                        'description' => 'nullable',
                        'remarks' => 'nullable',
                        'color' => 'nullable',
                        'size' => 'nullable',
                        'shade' => 'nullable',
                        'tex' => 'nullable',
                        'unit' => 'nullable',
                        'photo' => 'nullable',
                        'issue_to' => ($request->input('issue_type') === 'Self' || $request->input('issue_type') === 'Sample') ? 'required' : 'nullable',
                        'line' => $request->input('issue_type') === 'Self' ? 'required' : 'nullable',
                        'issuing_company' => ($request->input('issue_type') === 'Self' || $request->input('issue_type') === 'Sample') ? 'nullable' : 'required',
                        'challan_copy' => $request->input('issue_type') === 'Self' ? 'nullable|mimes:pdf' : 'required|mimes:pdf',
            ]);

            if ($validator->fails()) {
                return response()->json(['errors' => $validator->errors()], 422);
            }
            // Extract variables from the request
            $issue_type = $request->input('issue_type');
            $issue_to = $request->input('issue_to');
            $total_qty = $request->input('qty');
            $issuing_company = $request->input('issuing_company');
            $issue_qty = $request->input('issue_qty');
            $store_id = $request->input('id');

            if ($issue_qty <= $total_qty) {
                $issueData = $request->only([
                    'reference', 'issue_type', 'booking_item_id', 'booking_user_id', 'supplier_id', 'buyer_id', 'company_id',
                    'booking_id', 'budget_id', 'budget_item_id', 'techpack_id', 'description', 'remarks', 'challan_no', 'gate_pass',
                    'color', 'size', 'shade', 'tex', 'unit', 'photo', 'issue_to', 'line',
                ]);

                if ($request->hasFile('challan_copy')) {
                    $challanCopy = $request->file('challan_copy');
                    $challanCopyName = time() . '_' . $challanCopy->getClientOriginalName();
                    $challanCopy->move(public_path('challan-copies'), $challanCopyName);
                    $issueData['challan_copy'] = $challanCopyName;
                }

                if ($issue_type == "Self" || $issue_type == "Sample") {
                    $issue_user = \App\Models\User::where('id', $issue_to)->first();
                    $issue_company = $issue_user->company;
                } else {
                    $issue_company = $issuing_company;
                }

                $issue = Issue::create($issueData + [
                            'user_id' => $user_id,
                            'store_id' => $store_id,
                            'qty' => $issue_qty,
                            'issuing_company' => $issue_company,
                            'status' => 'Issued',
                ]);

                $store = \App\Models\Store::find($issue->store_id);
                $store->decrement('qty', $issue->qty);
                $return['data'] = $issue;
                $statusCode = 200;
                $return['status'] = 'success';
            } else {
                $return['errors']['issue_qty'] = 'Trying to insert greater than stock qty';
            }

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
            $issue = Issue::find($id);
            if ($issue) {
                $issue->supplier = optional(\App\Models\Supplier::find($issue->supplier_id))->company_name;
                $issue->buyer = optional(\App\Models\Buyer::find($issue->buyer_id))->name;
                $budget = \App\Models\Budget::find($issue->budget_id);
                $issue->buyer_id = optional($issue)->buyer_id;
                $booking = Booking::find($issue->booking_id);
                $issue->booking_number = $booking->booking_number;
                $booked_by = \App\Models\User::find($issue->booking_user_id);
                $issue->booked_by = $booked_by->full_name;
                $issue_by = \App\Models\User::find($issue->user_id);
                $issue->issue_by = $issue_by->full_name;
                $issue->supplier_id = optional($booking)->supplier_id;
                $issue->techpack = optional(\App\Models\Techpack::find($issue->techpack_id))->title;
                $company = \App\Models\Company::where('id', $issue->company_id)->first();
                $issue->company_name = $company->title;
                $budget_item = \App\Models\BudgetItem::find($issue->budget_item_id);
                $item = \App\Models\Item::find(optional($budget_item)->item_id);
                $issue->item_name = optional($item)->title;
                $issue->image_source = url('') . '/booking_items/' . $issue->photo;
                $issue->challan_file = url('') . '/challan-copies/' . $issue->challan_copy;

                $issue_to = \App\Models\User::find($issue->issue_to);
                if ($issue_to) {
                    $issue->issue_to_user = $issue_to->full_name;
                } else {
                    $issue->issue_to_user = "N/A";
                }

                $return['data'] = $issue;
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

}
