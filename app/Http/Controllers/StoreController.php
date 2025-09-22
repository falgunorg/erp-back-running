<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Store;
use App\Models\Booking;
use App\Models\BookingItem;
use App\Models\Team;
use App\Models\Receive;
use App\Models\Issue;
use App\Mail\StoreReportMail;

class StoreController extends Controller {

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

//            query builder
            $query = Store::orderBy('created_at', 'desc');
            // Apply the date range filter if both "from_date" and "to_date" are provided
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
                $val->total_received = \App\Models\Receive::where('store_id', $val->id)->sum('qty');
                $val->total_issued = \App\Models\Issue::where('store_id', $val->id)->sum('qty');
                $val->total_returned = \App\Models\ReturnBack::where('store_id', $val->id)->sum('qty');
                $val->total_used = $val->total_issued - $val->total_returned;
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
            $query = Store::where('company_id', $user->company)->orderBy('created_at', 'desc');

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
            $allData = Store::orderBy('created_at', 'desc')->get();
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
                $val->total_received = \App\Models\Receive::where('store_id', $val->id)->sum('qty');
                $val->total_issued = \App\Models\Issue::where('store_id', $val->id)->sum('qty');
                $val->total_returned = \App\Models\ReturnBack::where('store_id', $val->id)->sum('qty');
                $val->total_used = $val->total_issued - $val->total_returned;
            }
            $return['data'] = $stores;
            $return['allData'] = $allData;
            $statusCode = 200;
            return $this->response($return, $statusCode);
        } catch (\Throwable $ex) {
            return $this->error(['status' => 'error', 'main_error_message' => $ex->getMessage()]);
        }
    }

    public function show(Request $request) {
        try {
            $statusCode = 422;
            $return = [];
            $id = $request->input('id');
            $store = Store::find($id);
            if ($store) {
                $store->supplier = optional(\App\Models\Supplier::find($store->supplier_id))->company_name;
                $store->buyer = optional(\App\Models\Buyer::find($store->buyer_id))->name;
//                $budget = \App\Models\Budget::find($store->budget_id);
                $store->buyer_id = optional($store)->buyer_id;
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
                $receives = \App\Models\Receive::where('store_id', $store->id)->orderBy('created_at', 'desc')->get();
                $store->total_received = $receives->sum('qty');
                $return_back = \App\Models\ReturnBack::where('store_id', $store->id)->get();
                $store->returns = $return_back;
                $store->returned_qty = $return_back->sum('qty');

                foreach ($receives as $val) {
                    $user = \App\Models\User::find($val->user_id);
                    $val->received_by = $user->full_name;
                    $val->challan_file = url('') . '/challan-copies/' . $val->challan_copy;
                }
                $store->receives = $receives;
                $issues = \App\Models\Issue::where('store_id', $store->id)->orderBy('created_at', 'desc')->get();
                $store->total_issued = $issues->sum('qty');
                foreach ($issues as $val) {
                    $issue_user = \App\Models\User::find($val->user_id);
                    $val->issue_by = $issue_user->full_name;
                    $val->challan_file = url('') . '/challan-copies/' . $val->challan_copy;
                }
                $store->booking_qty = \App\Models\BookingItem::where('id', $store->booking_item_id)->sum('qty');
                $store->issues = $issues;
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

    public function store_summary(Request $request) {
        try {
            $statusCode = 422;
            $return = [];

            $user = \App\Models\User::find($request->user->id);

            $from_date = $request->input('from_date');
            $to_date = $request->input('to_date');
            $buyer_id = $request->input('buyer_id');
            $booking_id = $request->input('booking_id');
            $supplier_id = $request->input('supplier_id');
            $techpack_id = $request->input('techpack_id');
            $department = $request->input('department');
            $designation = $request->input('designation');
            $view = $request->input('view');
            $query = Store::orderBy('updated_at', 'desc');
            if ($department && $designation) {
                if ($department == "Merchandising" && $designation != "Deputy General Manager") {
                    if ($view) {
                        if ($view === 'self') {
                            $query->where('booking_user_id', $user->id);
                        } else if ($view === 'team') {
                            $find_user_team = Team::whereRaw("FIND_IN_SET('$user->id', employees)")->first();
                            $team_users = explode(',', $find_user_team->employees);
                            $query->whereIn('booking_user_id', $team_users);
                        }
                    }
                } else if ($department == "Audit" && $designation == "Assistant Manager") {
                    $query->where('company_id', $user->company);
                } else if ($department == "Audit" && $designation == "Manager") {
                    $query->orderBy('updated_at', 'desc');
                } else if ($department == "Management" && $designation == "Managing Director" || $department == "Merchandising" && $designation == "Deputy General Manager") {
                    $query->orderBy('updated_at', 'desc');
                } else if ($department == "Planing") {
                    $query->orderBy('updated_at', 'desc');
                }
            } else {
                $query->orderBy('created_at', 'desc');
            }


            // Apply the date range filter if both "from_date" and "to_date" are provided
            if ($from_date && $to_date) {
                $query->whereBetween('created_at', [$from_date, $to_date]);
            }
            if ($techpack_id) {
                $query->where('techpack_id', $techpack_id);
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
            $stores = $query->get();
            foreach ($stores as $val) {
                // Retrieve additional details for each booking item
                $budget = \App\Models\Budget::where('id', $val->budget_id)->first();
                $val->budget_number = $budget->budget_number;
                $purchase = \App\Models\Purchase::where('id', $budget->purchase_id)->first();
                $val->po_number = $purchase->po_number;
                $techpack = \App\Models\Techpack::where('id', $val->techpack_id)->first();
                $val->techpack = $techpack->title;
                $val->techpack_id = $techpack->id;
                $contract = \App\Models\PurchaseContract::where('id', $purchase->contract_id)->first();
                $val->contract = $contract->tag_number;
                $buyer = \App\Models\Buyer::where('id', $val->buyer_id)->first();
                $val->buyer = $buyer->name;
                $user = \App\Models\User::where('id', $val->booking_user_id)->first();
                $val->user = $user->full_name;
                $supplier = \App\Models\Supplier::where('id', $val->supplier_id)->first();
                $val->supplier = $supplier->company_name;
                $company = \App\Models\Company::where('id', $val->company_id)->first();
                $val->company = $company->title;
                $booking = Booking::where('id', $val->booking_id)->first();
                $val->booking_date = $booking->booking_date;
                $val->delivery_date = $booking->delivery_date;
                $val->booking_to = $booking->booking_to;
                $val->booking_number = $booking->booking_number;
                $budget_item = \App\Models\BudgetItem::where('id', $val->budget_item_id)->first();
                $item = \App\Models\Item::where('id', $budget_item->item_id)->first();
                $val->item_name = $item->title;
                $val->image_source = url('') . '/booking_items/' . $val->photo;
                $val->booking_qty = BookingItem::where('id', $val->booking_item_id)->sum('qty');
                $val->total_received = \App\Models\Receive::where('store_id', $val->id)->sum('qty');
                $val->left_receive = $val->booking_qty - $val->total_received;
                $val->total_issued = \App\Models\Issue::where('store_id', $val->id)->sum('qty');
                $val->total_returned = \App\Models\ReturnBack::where('store_id', $val->id)->sum('qty');
                $val->total_used = $val->total_issued - $val->total_returned;
                $val->balance = $val->qty;
            }
            // Check if any booking items were found
            if (!empty($stores)) {
                $statusCode = 200;
                $return['status'] = 'success';
                $return['data'] = $stores;
            } else {
                $return['status'] = 'error';
            }
            return $this->response($return, $statusCode);
        } catch (\Throwable $ex) {
            return $this->error(['status' => "error", 'main_error_message' => $ex->getMessage(), 'at Line' => $ex->getLine()]);
        }
    }

    public function return_request(Request $request) {
        try {
            $statusCode = 422;
            $return = [];
            $user = \App\Models\User::find($request->user->id);
            $from_date = $request->input('from_date');
            $to_date = $request->input('to_date');
            $num_of_row = $request->input('num_of_row');
            $filter_items = $request->input('filter_items');
            $store_id = $request->input('store_id');
            $issue_id = $request->input('issue_id');
            $query = \App\Models\ReturnBack::where('return_to', $user->id)->orderBy('created_at', 'desc');
            if ($from_date && $to_date) {
                $query->whereBetween('created_at', [$from_date, $to_date]);
            }
            if ($store_id) {
                $query->where('store_id', $store_id);
            }
            if ($filter_items) {
                $query->whereIn('id', $filter_items);
            }
            if ($issue_id) {
                $query->where('issue_id', $issue_id);
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
                $val->issued_qty = \App\Models\Issue::where('id', $val->issue_id)->sum('qty');
                $return_by = \App\Models\User::find($val->user_id);
                if ($return_by) {
                    $val->return_by_user = $return_by->full_name;
                } else {
                    $val->return_by_user = 'N/A';
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

    public function receive_return_request(Request $request) {
        try {
            $statusCode = 422;
            $return = [];
            $id = $request->input('id');
            $return_item = \App\Models\ReturnBack::find($id);
            if ($return_item) {
                $store = Store::where('id', $return_item->store_id)->first();
                if ($store) {
                    $store->increment('qty', $return_item->qty);
                    $return_item->status = 'Received';
                    $return_item->received_by = $request->user->id;

                    $return_item->save();

                    $return['data'] = $return_item;
                    $statusCode = 200;
                    $return['status'] = 'success';
                }
            }
            return $this->response($return, $statusCode);
        } catch (\Throwable $ex) {
            return $this->error(['status' => 'error', 'main_error_message' => $ex->getMessage()]);
        }
    }

    //MAIL SENDING
    public function mail_daily_report(Request $request) {
        // Retrieve users based on conditions
        $users = User::whereIn('id', [1, 57])
                ->get();

        // Group users by company_id
        $usersByCompany = $users->groupBy('company');

        // Iterate over each company
        foreach ($usersByCompany as $companyId => $companyUsers) {
            // Gather data for the report for this company
            $reportData = $this->generateReport($companyId);

            // Send email to users of this company
            $this->sendReportEmail($companyUsers, $reportData);
        }
    }

    // Generate report for a company
    private function generateReport($companyId) {
        // Retrieve data for the report based on company_id
        $receives = Receive::where('company_id', $companyId)
                ->whereDate('created_at', now()->toDateString())
                ->get();

        foreach ($receives as $val) {
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

        $issues = Issue::where('company_id', $companyId)
                ->whereDate('created_at', now()->toDateString())
                ->get();

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

        $storeSummary = Store::where('company_id', $companyId)->get();

        foreach ($storeSummary as $val) {
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
            $val->total_received = \App\Models\Receive::where('store_id', $val->id)->sum('qty');
            $val->total_issued = \App\Models\Issue::where('store_id', $val->id)->sum('qty');
            $val->total_returned = \App\Models\ReturnBack::where('store_id', $val->id)->sum('qty');
            $val->total_used = $val->total_issued - $val->total_returned;
        }

        // Assemble the report data
        $reportData = [
            'receives' => $receives,
            'issues' => $issues,
            'summary' => $storeSummary,
        ];

        return $reportData;
    }

    // Send email to users of a company
    private function sendReportEmail($users, $reportData) {
        foreach ($users as $user) {
            $username = $user->full_name;
            Mail::to($user->email)->send(new StoreReportMail($reportData, $username));
        }
    }

}
