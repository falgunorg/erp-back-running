<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Receive;
use Illuminate\Support\Facades\Validator;
use App\Models\Booking;
use App\Models\Store;

class ReceiveController extends Controller {

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
            $techpack_id = $request->input('techpack_id');
            $company_id = $request->input('company_id');
            $query = Receive::orderBy('created_at', 'desc');

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
            // Limit the result to "num_of_row" records
            $stores = $query->take($num_of_row)->get();

            foreach ($stores as $val) {
                $val->supplier = optional(\App\Models\Supplier::find($val->supplier_id))->company_name;
                $val->buyer = optional(\App\Models\Buyer::find($val->buyer_id))->name;
                $budget = \App\Models\Budget::find($val->budget_id);
                $val->buyer_id = optional($budget)->buyer_id;
                $booking = Booking::find($val->booking_id);
                $val->booking_number = $booking->booking_number;
                $booked_by = \App\Models\User::find($val->booking_user_id);
                $val->booked_by = $booked_by->full_name;
                $received_by = \App\Models\User::find($val->user_id);
                $val->received_by = $received_by->full_name;
                $val->supplier_id = optional($booking)->supplier_id;
                $val->techpack = optional(\App\Models\Techpack::find($val->techpack_id))->title;
                $budget_item = \App\Models\BudgetItem::find($val->budget_item_id);
                $item = \App\Models\Item::find(optional($budget_item)->item_id);
                $val->item_name = optional($item)->title;
                $val->image_source = url('') . '/booking_items/' . $val->photo;
                $val->challan_file = url('') . '/challan-copies/' . $val->challan_copy;
                $val->company_name = optional(\App\Models\Company::find($val->company_id))->title;
            }
            $return['data'] = $stores;
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
            $query = Receive::where('company_id', $user->company)->orderBy('created_at', 'desc');
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
            // Limit the result to "num_of_row" records
            $stores = $query->take($num_of_row)->get();
            // Retrieve all data without additional filters
            $allData = Receive::orderBy('created_at', 'desc')->get();
            foreach ($stores as $val) {
                $val->supplier = optional(\App\Models\Supplier::find($val->supplier_id))->company_name;
                $val->buyer = optional(\App\Models\Buyer::find($val->buyer_id))->name;
                $budget = \App\Models\Budget::find($val->budget_id);
                $val->buyer_id = optional($budget)->buyer_id;
                $booking = Booking::find($val->booking_id);
                $val->booking_number = $booking->booking_number;
                $booked_by = \App\Models\User::find($val->booking_user_id);
                $val->booked_by = $booked_by->full_name;
                $received_by = \App\Models\User::find($val->user_id);
                $val->received_by = $received_by->full_name;
                $val->supplier_id = optional($booking)->supplier_id;
                $val->techpack = optional(\App\Models\Techpack::find($val->techpack_id))->title;
                $budget_item = \App\Models\BudgetItem::find($val->budget_item_id);
                $item = \App\Models\Item::find(optional($budget_item)->item_id);
                $val->item_name = optional($item)->title;
                $val->image_source = url('') . '/booking_items/' . $val->photo;
                $val->challan_file = url('') . '/challan-copies/' . $val->challan_copy;
            }
            $return['data'] = $stores;
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
            $user = \App\Models\User::find($request->user->id);

            $validator = Validator::make($request->all(), [
                        'id' => 'required',
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
                        'receive_qty' => 'required',
                        'qty' => 'required',
                        'description' => 'nullable',
                        'remarks' => 'nullable',
                        'color' => 'nullable',
                        'size' => 'nullable',
                        'shade' => 'nullable',
                        'tex' => 'nullable',
                        'unit' => 'nullable',
                        'photo' => 'nullable',
                        'challan_copy' => 'nullable|mimes:pdf', // Add validation for PDF files
            ]);

            if ($validator->fails()) {
                return response()->json(['errors' => $validator->errors()], 422);
            }

            $req_qty = $request->input('qty');
            $receive_qty = $request->input('receive_qty');
            $booking_id = $request->input('booking_id');
            $booking_item_id = $request->input('id');
            $find_store = Store::where('booking_id', $booking_id)->where('booking_item_id', $booking_item_id)->first();
            $already_received = Receive::where('booking_id', $booking_id)->where('booking_item_id', $booking_item_id)->sum('qty');

            if ($find_store) {
                if (($already_received + $receive_qty) <= $req_qty) {
                    $receive = new Receive($request->only([
                                'booking_user_id', 'supplier_id', 'buyer_id', 'company_id', 'booking_id',
                                'budget_id', 'budget_item_id', 'techpack_id', 'description', 'remarks', 'challan_no', 'gate_pass',
                                'color', 'size', 'shade', 'tex', 'unit', 'photo'
                    ]));

                    if ($request->hasFile('challan_copy')) {
                        $challanCopy = $request->file('challan_copy');
                        $challanCopyName = time() . '_' . $challanCopy->getClientOriginalName();
                        $challanCopy->move(public_path('challan-copies'), $challanCopyName);
                        $receive->challan_copy = $challanCopyName;
                    }

                    $receive->user_id = $user_id;
                    $receive->store_id = $find_store->id;
                    $receive->booking_item_id = $booking_item_id;
                    $receive->qty = $receive_qty;

                    if ($receive->save()) {
                        $find_store->increment('qty', $receive->qty);
                        $return['data'] = $receive;
                        $statusCode = 200;
                        $return['status'] = 'success';
                    } else {
                        $return['errors']['challan_copy'] = 'Failed to save receive record.';
                    }
                } else {
                    $return['errors']['receive_qty'] = 'Trying to insert greater than booking qty';
                }
            } else {
                if ($receive_qty <= $req_qty) {
                    $store = new Store($request->only([
                                'booking_user_id', 'supplier_id', 'buyer_id', 'company_id', 'booking_id',
                                'budget_id', 'budget_item_id', 'techpack_id', 'description', 'remarks', 'challan_no', 'gate_pass',
                                'color', 'size', 'shade', 'tex', 'unit', 'photo'
                    ]));

                    if ($request->hasFile('challan_copy')) {
                        $challanCopy = $request->file('challan_copy');
                        $challanCopyName = time() . '_' . $challanCopy->getClientOriginalName();
                        $challanCopy->move(public_path('challan-copies'), $challanCopyName);
//                        $store->challan_copy = $challanCopyName;
                    }

                    $store->user_id = $user_id;
                    $store->booking_item_id = $booking_item_id;
                    $store->qty = $receive_qty;

                    if ($store->save()) {
                        $receive = new Receive($request->only([
                                    'booking_user_id', 'supplier_id', 'buyer_id', 'company_id', 'booking_id',
                                    'budget_id', 'budget_item_id', 'techpack_id', 'description', 'remarks', 'challan_no', 'gate_pass',
                                    'color', 'size', 'shade', 'tex', 'unit', 'photo'
                        ]));

                        if ($request->hasFile('challan_copy')) {
                            $receive->challan_copy = $challanCopyName;
                        }

                        $receive->store_id = $store->id;
                        $receive->user_id = $user_id;
                        $receive->booking_item_id = $booking_item_id;
                        $receive->qty = $receive_qty;

                        if ($receive->save()) {
                            $return['data'] = $store;
                            $statusCode = 200;
                            $return['status'] = 'success';
                        } else {
                            $return['errors']['challan_copy'] = 'Failed to save receive record.';
                        }
                    } else {
                        $return['errors']['challan_copy'] = 'Failed to save store record.';
                    }
                } else {
                    $return['errors']['receive_qty'] = 'Trying to insert greater than booking qty';
                }
            }

            $notification = new \App\Models\Notification;
            $notification->title = "Item Received By " . $user->full_name;
            $notification->receiver = $request->input('booking_user_id');
            $notification->url = "/store/store-summary";
            $notification->description = "Please Take Necessary Action";
            $notification->is_read = 0;
            $notification->save();

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
            $store = Receive::find($id);
            if ($store) {
                $store->supplier = optional(\App\Models\Supplier::find($store->supplier_id))->company_name;
                $store->buyer = optional(\App\Models\Buyer::find($store->buyer_id))->name;
                $budget = \App\Models\Budget::find($store->budget_id);
                $store->buyer_id = optional($budget)->buyer_id;
                $booking = Booking::find($store->booking_id);
                $store->booking_number = $booking->booking_number;
                $booked_by = \App\Models\User::find($store->booking_user_id);
                $store->booked_by = $booked_by->full_name;
                $received_by = \App\Models\User::find($store->user_id);
                $store->received_by = $received_by->full_name;
                $store->supplier_id = optional($booking)->supplier_id;
                $store->techpack = optional(\App\Models\Techpack::find($store->techpack_id))->title;
                $budget_item = \App\Models\BudgetItem::find($store->budget_item_id);
                $item = \App\Models\Item::find(optional($budget_item)->item_id);
                $store->item_name = optional($item)->title;
                $store->image_source = url('') . '/booking_items/' . $store->photo;
                $store->challan_file = url('') . '/challan-copies/' . $store->challan_copy;
                $return['data'] = $store;
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
