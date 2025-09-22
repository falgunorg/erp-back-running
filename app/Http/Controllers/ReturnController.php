<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Store;
use App\Models\ReturnBack;
use App\Models\Issue;
use App\Models\Booking;
use Illuminate\Support\Facades\Validator;

//Receive and Return items Here for store

class ReturnController extends Controller {

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
            $techpack_id = $request->input('techpack_id');
            $query = ReturnBack::where('user_id', $user->id)->orderBy('created_at', 'desc');
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
            $returns = $query->take($num_of_row)->get();
            foreach ($returns as $val) {
                $store = Store::find($val->store_id);
                $val->buyer = optional(\App\Models\Buyer::find($store->buyer_id))->name;
                $val->techpack = optional(\App\Models\Techpack::find($store->techpack_id))->title;
                $val->image_source = url('') . '/booking_items/' . $store->photo;
                $val->user = optional(\App\Models\User::find($val->user_id))->full_name;
                $val->return_to_user = optional(\App\Models\User::find($val->return_to))->full_name;
                $val->delivery_challan = optional(\App\Models\Issue::find($val->issue_id))->delivery_challan;

                $val->unit = optional(\App\Models\Store::find($val->store_id))->unit;

                $received_by = \App\Models\User::find($val->received_by);
                if ($received_by) {
                    $val->received_by_user = $received_by->full_name;
                } else {
                    $val->received_by_user = 'N/A';
                }
            }
            $return['data'] = $returns;
            $statusCode = 200;
            $return['status'] = 'success';

            return $this->response($return, $statusCode);
        } catch (\Throwable $ex) {
            return $this->error(['status' => 'error', 'main_error_message' => $ex->getMessage()]);
        }
    }

    public function store(Request $request) {
        try {
            $statusCode = 422;
            $return = [];
            $user = \App\Models\User::find($request->user->id);

            $validator = Validator::make($request->all(), [
                        'id' => 'required',
                        'qty' => 'required',
                        'return_qty' => 'required',
                        'store_id' => 'required',
                        'user_id' => 'required',
                        'company_id' => 'required',
                        'remarks' => 'nullable',
            ]);

            if ($validator->fails()) {
                return response()->json(['errors' => $validator->errors()], 422);
            }

            $issue_id = $request->input('id');
            $return_qty = $request->input('return_qty');
            $balance_qty = $request->input('qty');

            $already_return = ReturnBack::where('issue_id', $issue_id)->sum('qty');

            if ($already_return + $return_qty <= $balance_qty) {
                $entry = new ReturnBack;
                $entry->user_id = $user->id;
                $entry->return_to = $request->input('user_id');
                $entry->store_id = $request->input('store_id');
                $entry->issue_id = $issue_id;
                $entry->company_id = $request->input('company_id');
                $entry->qty = $return_qty;
                $entry->status = "Pending";
                $entry->received_by = 0;
                $entry->remarks = $request->input('remarks');
                $entry->save();
                $return['data'] = $entry;
                $statusCode = 200;
                $return['status'] = 'success';
            } else {
                $return['errors']['return_qty'] = 'Trying to return greater than balance qty';
            }
            return $this->response($return, $statusCode);
        } catch (\Throwable $ex) {
            return $this->error(['status' => 'error', 'main_error_message' => $ex->getMessage()]);
        }
    }

    public function show(Request $request) {
        try {
            $statusCode = 422;
            $return = [];
            $item = ReturnBack::find($request->input('id'));
            if ($item) {
                $store = Store::find($item->store_id);
                $item->buyer = optional(\App\Models\Buyer::find($store->buyer_id))->name;
                $item->techpack = optional(\App\Models\Techpack::find($store->techpack_id))->title;
                $item->image_source = url('') . '/booking_items/' . $store->photo;
                $item->user = optional(\App\Models\User::find($item->user_id))->full_name;
                $item->return_to_user = optional(\App\Models\User::find($item->return_to))->full_name;
                $received_by = \App\Models\User::find($item->received_by);
                if ($received_by) {
                    $item->received_by_user = $received_by->full_name;
                } else {
                    $item->received_by_user = 'N/A';
                }
                $return['data'] = $item;
                $statusCode = 200;
                $return['status'] = 'success';
            }



            return $this->response($return, $statusCode);
        } catch (\Throwable $ex) {
            return $this->error(['status' => 'error', 'main_error_message' => $ex->getMessage()]);
        }
    }

    public function update(Request $request) {
        try {
            $statusCode = 422;
            $return = [];
            $user = \App\Models\User::find($request->user->id);
            $validator = Validator::make($request->all(), [
                        'issue_id' => 'required',
                        'qty' => 'required',
                        'store_id' => 'required',
                        'return_to' => 'required',
                        'company_id' => 'required',
                        'remarks' => 'nullable',
            ]);

            if ($validator->fails()) {
                return response()->json(['errors' => $validator->errors()], 422);
            }
            $entry = ReturnBack::find($request->input('id'));
            $entry->user_id = $user->id;
            $entry->return_to = $request->input('return_to');
            $entry->store_id = $request->input('store_id');
            $entry->issue_id = $request->input('issue_id');
            $entry->company_id = $request->input('company_id');
            $entry->qty = $request->input('qty');
            $entry->status = "Pending";
            $entry->received_by = 0;
            $entry->remarks = $request->input('remarks');
            $entry->save();

            $return['data'] = $entry;
            $statusCode = 200;
            $return['status'] = 'success';

            return $this->response($return, $statusCode);
        } catch (\Throwable $ex) {
            return $this->error(['status' => 'error', 'main_error_message' => $ex->getMessage()]);
        }
    }

    public function destroy(Request $request) {
        try {
            $statusCode = 422;
            $return = [];

            $delete = ReturnBack::where('id', $request->input('id'))->delete();
            if ($delete) {
                $return['data'] = $delete;
                $statusCode = 200;
                $return['status'] = 'success';
            }
            return $this->response($return, $statusCode);
        } catch (\Throwable $ex) {
            return $this->error(['status' => 'error', 'main_error_message' => $ex->getMessage()]);
        }
    }

    public function issued_to_me(Request $request) {
        try {
            $statusCode = 422;
            $return = [];
            $user = \App\Models\User::find($request->user->id);
            $from_date = $request->input('from_date');
            $to_date = $request->input('to_date');
            $num_of_row = $request->input('num_of_row');
            $filter_items = $request->input('filter_items');
            $buyer_id = $request->input('buyer_id');
            $techpack_id = $request->input('techpack_id');
            $query = Issue::where('issue_to', $user->id)->orderBy('created_at', 'desc');
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
            $issues = $query->take($num_of_row)->get();
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
                $already_return = ReturnBack::where('issue_id', $val->id)->sum('qty');
                $val->returned_qty = $already_return;
            }
            $return['data'] = $issues;
            $statusCode = 200;
            $return['status'] = 'success';

            return $this->response($return, $statusCode);
        } catch (\Throwable $ex) {
            return $this->error(['status' => 'error', 'main_error_message' => $ex->getMessage()]);
        }
    }

}
