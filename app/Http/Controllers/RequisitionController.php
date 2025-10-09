<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Requisition;
use App\Models\RequisitionItem;
use Illuminate\Support\Facades\Validator;
use App\Models\Part;

class RequisitionController extends Controller {

    public function index_for_receive(Request $request) {
        try {
            $statusCode = 422;
            $return = [];
            $user = \App\Models\User::find($request->user->id);
            $requisitions = Requisition::where('user_id', $user->id)
                            ->where('status', 'Finalized')
                            ->orderBy('created_at', 'desc')->get();

            $return['data'] = $requisitions;
            $return['status'] = 'success';
            $statusCode = 200;

            return $this->response($return, $statusCode);
        } catch (\Throwable $ex) {
            return $this->error(['status' => 'error', 'main_error_message' => $ex->getMessage(), 'AT LINE' => $ex->getLine()]);
        }
    }

    public function index(Request $request) {
        try {
            $statusCode = 422;
            $return = [];
            $department_title = $request->input('department');
            $designation_title = $request->input('designation');
            $show_all = $request->input('show_all');
            $company_wise = $request->input('company_wise');
            $from_date = $request->input('from_date');
            $to_date = $request->input('to_date');
            $status = $request->input('status');
            $item_id = $request->input('id');
            $searchValue = $request->input('searchValue');
            $department_id = $request->input('department_id');
            $label = $request->input('label');
            $user = \App\Models\User::find($request->user->id);

            // Base query
            $query = Requisition::query();
            // Own requisition query
            $own_requisition = Requisition::where('user_id', $user->id);

            // Conditions based on department and designation
            if (($department_title == 'Store' || $department_title == 'Administration' || $department_title == 'IT' || $department_title == 'Maintenance' || $department_title == 'Washing') && $designation_title != 'Manager') {
                $query->where('user_id', $user->id);
            } else if (($department_title == 'Store' || $department_title == 'Administration' || $department_title == 'IT' || $department_title == 'Maintenance' || $department_title == 'Washing') && $designation_title == 'Manager') {
                $query->where('recommended_user', $user->id)->whereNotIn('status', ['Pending', 'Rejected']);
            } else if ($department_title == 'Purchase' && $designation_title != 'Manager') {
                $query->whereNotIn('status', ['Pending', 'Rejected'])->where('company_id', $user->company);
            } else if ($department_title == 'Purchase' && $designation_title == 'Manager') {
                $query->whereNotIn('status', ['Pending', 'Rejected']);
            } else if ($department_title == 'Audit' && $designation_title != 'Manager') {
                $query->where('company_id', $user->company)->whereNotIn('status', ['Pending', 'Rejected']);
            } else if ($department_title == 'Audit' && $designation_title == 'Manager') {
                $query->where('company_id', $user->company)->whereNotIn('status', ['Pending', 'Rejected']);
            } else if ($department_title == "Management" && $designation_title == "Factory Incharge") {
                $query->where('company_id', $user->company)->whereNotIn('status', ['Pending', 'Rejected']);
            } else if ($department_title == 'Accounts & Finance' && $designation_title == 'General Manager') {
                $query->where('company_id', $user->company)->whereNotIn('status', ['Pending', 'Rejected']);
            } else if ($department_title == 'Accounts & Finance' && $designation_title != 'General Manager') {
                $query->whereNotIn('status', ['Pending', 'Rejected']);
            } else if ($department_title == 'Administration' && $designation_title == 'Receptionist') {
                $query->where('company_id', $user->company);
            } else if ($show_all == 'true' && $company_wise == 'true') {
                $query->where('company_id', $user->company);
            }
            // Apply date range filter if both "from_date" and "to_date" are provided
            if ($from_date && $to_date) {
                $to_date = date('Y-m-d', strtotime($to_date . ' +1 day'));
                $query->whereBetween('created_at', [$from_date, $to_date]);
                $own_requisition->whereBetween('created_at', [$from_date, $to_date]);
            }
            if ($status) {
                $query->where('status', $status);
                $own_requisition->where('status', $status);
            }

            if ($department_id) {
                $query->where('department', $department_id);
                $own_requisition->where('department', $department_id);
            }


            if ($label) {
                $query->where('label', 'like', "%{$label}%");
                $own_requisition->where('label', 'like', "%{$label}%");
            }



            if ($item_id) {
                $query->where('id', $item_id);
                $own_requisition->where('id', $item_id);
            }

            if ($searchValue) {
                $query->where('requisition_number', $searchValue);
                $own_requisition->where('requisition_number', $searchValue);
            }

            // Combine the queries and remove duplicates
            $combinedQuery = $query->union($own_requisition);

            // Final ordering and pagination
            $requisitions = Requisition::fromSub($combinedQuery, 'combined')
                    ->orderBy('created_at', 'desc')
                    ->paginate(100);

            // If there are requisitions, process them

            foreach ($requisitions as $val) {
                $user = \App\Models\User::find($val->user_id);
                $val->requisition_by = $user->full_name;
                $company = \App\Models\Company::find($val->company_id);
                $val->company = $company->title;
                $department = \App\Models\Department::find($val->department);
                $val->department_title = $department->title;
                $billing_company = \App\Models\Company::find($val->billing_company_id);
                $val->billing_company = $billing_company->title;
                $total_approx_amount = RequisitionItem::where('requisition_id', $val->id)
                        ->selectRaw('SUM(recommand_qty * rate) as approx_total')
                        ->value('approx_total');

                $total_items = RequisitionItem::where('requisition_id', $val->id)->count();
                $total_purchesed_items = RequisitionItem::where('requisition_id', $val->id)->whereIn('status', ['Purchased', 'Inhoused'])->count();
                $total_partial_purchased_items = RequisitionItem::where('requisition_id', $val->id)->where('status', 'Pending')->count();
                $total_not_purchased = RequisitionItem::where('requisition_id', $val->id)->where('status', 'Listed')->count();
                $val->total_items = $total_items;
                $val->purchesed_items = $total_purchesed_items;
                $val->partial_purchased = $total_partial_purchased_items;
                $val->left_purchase_items = $total_not_purchased;
                $val->total_approx_amount = $total_approx_amount ?? 0;
                if ($total_items > 0) {
                    $val->purchase_percentage = number_format(($total_purchesed_items / $total_items) * 100, 2);
                    $val->partial_purchase_percentage = number_format(($total_partial_purchased_items / $total_items) * 100, 2);
                } else {
                    $val->purchase_percentage = number_format(0, 2); // To handle division by zero in case total_items is 0
                    $val->partial_purchase_percentage = number_format(0, 2);
                }
            }



            $all_data = Requisition::orderBy('created_at', 'desc')
                    ->paginate(100);
            foreach ($all_data as $val) {
                $user = \App\Models\User::find($val->user_id);
                $val->requisition_by = $user->full_name;
                $company = \App\Models\Company::find($val->company_id);
                $val->company = $company->title;
                $department = \App\Models\Department::find($val->department);
                $val->department_title = $department->title;
                $billing_company = \App\Models\Company::find($val->billing_company_id);
                $val->billing_company = $billing_company->title;

                $total_items = RequisitionItem::where('requisition_id', $val->id)->count();
                $total_purchesed_items = RequisitionItem::where('requisition_id', $val->id)->whereIn('status', ['Purchased', 'Inhoused'])->count();
                $total_partial_purchased_items = RequisitionItem::where('requisition_id', $val->id)->where('status', 'Pending')->count();
                $total_not_purchased = RequisitionItem::where('requisition_id', $val->id)->where('status', 'Listed')->count();
                $val->total_items = $total_items;
                $val->purchesed_items = $total_purchesed_items;
                $val->partial_purchased = $total_partial_purchased_items;
                $val->left_purchase_items = $total_not_purchased;
                if ($total_items > 0) {
                    $val->purchase_percentage = number_format(($total_purchesed_items / $total_items) * 100, 2);
                    $val->partial_purchase_percentage = number_format(($total_partial_purchased_items / $total_items) * 100, 2);
                } else {
                    $val->purchase_percentage = number_format(0, 2); // To handle division by zero in case total_items is 0
                    $val->partial_purchase_percentage = number_format(0, 2);
                }
            }

            $return['all_data'] = $all_data;
            $return['requisitions'] = $requisitions;
            $statusCode = 200;

            return $this->response($return, $statusCode);
        } catch (\Throwable $ex) {
            return $this->error(['status' => 'error', 'main_error_message' => $ex->getMessage(), 'AT LINE' => $ex->getLine()]);
        }
    }

    public function index_special(Request $request) {
        try {
            $statusCode = 422;
            $return = [];
            $company_id = $request->input('company_id');
            $from_date = $request->input('from_date');
            $to_date = $request->input('to_date');
            $status = $request->input('status');
            $item_id = $request->input('id');
            $searchValue = $request->input('searchValue');
            // Base query
            $query = Requisition::whereNotIn('status', ['Pending', 'Rejected'])
                    ->where('company_id', $company_id);
            if ($from_date && $to_date) {
                $to_date = date('Y-m-d', strtotime($to_date . ' +1 day'));
                $query->whereBetween('created_at', [$from_date, $to_date]);
            }
            if ($status) {
                $query->where('status', $status);
            }
            if ($item_id) {
                $query->where('id', $item_id);
            }
            if ($searchValue) {
                $query->where('requisition_number', $searchValue);
            }

            // Final ordering and pagination
            $requisitions = $query->orderBy('created_at', 'desc')
                    ->paginate(100);
            \Log::info($requisitions->pluck('created_at'));

            foreach ($requisitions as $val) {
                $user = \App\Models\User::find($val->user_id);
                $val->requisition_by = $user->full_name ?? 'Unknown';
                $company = \App\Models\Company::find($val->company_id);
                $val->company = $company->title ?? 'Unknown';
                $department = \App\Models\Department::find($val->department);
                $val->department_title = $department->title ?? 'Unknown';
                $billing_company = \App\Models\Company::find($val->billing_company_id);
                $val->billing_company = $billing_company->title ?? 'Unknown';

                $total_items = RequisitionItem::where('requisition_id', $val->id)->count();
                $total_purchesed_items = RequisitionItem::where('requisition_id', $val->id)->whereIn('status', ['Purchased', 'Inhoused'])->count();
                $total_partial_purchased_items = RequisitionItem::where('requisition_id', $val->id)->where('status', 'Pending')->count();
                $total_not_purchased = RequisitionItem::where('requisition_id', $val->id)->where('status', 'Listed')->count();
                $val->total_items = $total_items;
                $val->purchesed_items = $total_purchesed_items;
                $val->partial_purchased = $total_partial_purchased_items;
                $val->left_purchase_items = $total_not_purchased;
                if ($total_items > 0) {
                    $val->purchase_percentage = number_format(($total_purchesed_items / $total_items) * 100, 2);
                    $val->partial_purchase_percentage = number_format(($total_partial_purchased_items / $total_items) * 100, 2);
                } else {
                    $val->purchase_percentage = number_format(0, 2); // To handle division by zero in case total_items is 0
                    $val->partial_purchase_percentage = number_format(0, 2);
                }
            }

            $return['requisitions'] = $requisitions;
            $return['status'] = 'success';
            $statusCode = 200;
            return $this->response($return, $statusCode);
        } catch (\Throwable $ex) {
            return $this->error([
                        'status' => 'error',
                        'main_error_message' => $ex->getMessage(),
                        'AT LINE' => $ex->getLine()
            ]);
        }
    }

    public function index_bk(Request $request) {
        try {
            $statusCode = 422;
            $return = [];
            $department_title = $request->input('department');
            $designation_title = $request->input('designation');
            $show_all = $request->input('show_all');
            $company_wise = $request->input('company_wise');

            $from_date = $request->input('from_date');
            $to_date = $request->input('to_date');
            $status = $request->input('status');
            $item_id = $request->input('id');
            $user = \App\Models\User::find($request->user->id);

            // Base query
            $query = Requisition::query();
            // Own requisition query
            $own_requisition = Requisition::where('user_id', $user->id);

            // Conditions based on department and designation
            if (($department_title == 'Store' || $department_title == 'Administration' || $department_title == 'IT' || $department_title == 'Maintenance' || $department_title == 'Washing') && $designation_title != 'Manager') {
                $query->where('user_id', $user->id);
            } else if (($department_title == 'Store' || $department_title == 'Administration' || $department_title == 'IT' || $department_title == 'Maintenance' || $department_title == 'Washing') && $designation_title == 'Manager') {
                $query->where('recommended_user', $user->id)->whereNotIn('status', ['Pending', 'Rejected']);
            } else if ($department_title == 'Purchase' && $designation_title != 'Manager') {
                $query->whereNotIn('status', ['Pending', 'Rejected', 'Placed'])->where('company_id', $user->company);
            } else if ($department_title == 'Purchase' && $designation_title == 'Manager') {
                $query->whereNotIn('status', ['Pending', 'Rejected', 'Placed']);
            } else if ($department_title == 'Audit' && $designation_title != 'Manager') {
                $query->where('company_id', $user->company)->whereNotIn('status', ['Pending', 'Rejected', 'Placed', 'Recommended']);
            } else if ($department_title == 'Audit' && $designation_title == 'Manager') {
                $query->where('company_id', $user->company)->whereNotIn('status', ['Pending', 'Rejected', 'Placed', 'Recommended']);
            } else if ($department_title == "Management" && $designation_title == "Factory Incharge") {
                $query->where('company_id', $user->company)->whereNotIn('status', ['Pending', 'Rejected', 'Placed', 'Recommended']);
            } else if ($department_title == 'Accounts & Finance' && $designation_title == 'General Manager') {
                $query->where('company_id', $user->company)->whereNotIn('status', ['Pending', 'Rejected', 'Placed', 'Recommended']);
            } else if ($department_title == 'Accounts & Finance' && $designation_title != 'General Manager') {
                $query->whereNotIn('status', ['Pending', 'Rejected', 'Placed', 'Recommended']);
            } else if ($department_title == 'Administration' && $designation_title == 'Receptionist') {
                $query->where('company_id', $user->company);
            } else if ($show_all == 'true' && $company_wise == 'true') {
                $query->where('company_id', $user->company);
            }
            // Apply date range filter if both "from_date" and "to_date" are provided
            if ($from_date && $to_date) {
                $to_date = date('Y-m-d', strtotime($to_date . ' +1 day'));
                $query->whereBetween('created_at', [$from_date, $to_date]);
                $own_requisition->whereBetween('created_at', [$from_date, $to_date]);
            }
            if ($status) {
                $query->where('status', $status);
                $own_requisition->where('status', $status);
            }
            if ($item_id) {
                $query->where('id', $item_id);
                $own_requisition->where('id', $item_id);
            }

            // Combine the queries and remove duplicates
            $combinedQuery = $query->union($own_requisition);

            // Final ordering and pagination
            $requisitions = Requisition::fromSub($combinedQuery, 'combined')
                    ->orderBy('created_at', 'desc')
                    ->paginate(100);

            // If there are requisitions, process them

            foreach ($requisitions as $val) {
                $user = \App\Models\User::find($val->user_id);
                $val->requisition_by = $user->full_name;
                $company = \App\Models\Company::find($val->company_id);
                $val->company = $company->title;
                $department = \App\Models\Department::find($val->department);
                $val->department_title = $department->title;
                $billing_company = \App\Models\Company::find($val->billing_company_id);
                $val->billing_company = $billing_company->title;
            }



            $all_data = Requisition::orderBy('created_at', 'desc')
                    ->paginate(100);
            foreach ($all_data as $val) {
                $user = \App\Models\User::find($val->user_id);
                $val->requisition_by = $user->full_name;
                $company = \App\Models\Company::find($val->company_id);
                $val->company = $company->title;
                $department = \App\Models\Department::find($val->department);
                $val->department_title = $department->title;
                $billing_company = \App\Models\Company::find($val->billing_company_id);
                $val->billing_company = $billing_company->title;
            }

            $return['all_data'] = $all_data;
            $return['requisitions'] = $requisitions;
            $statusCode = 200;

            return $this->response($return, $statusCode);
        } catch (\Throwable $ex) {
            return $this->error(['status' => 'error', 'main_error_message' => $ex->getMessage(), 'AT LINE' => $ex->getLine()]);
        }
    }

    public function store(Request $request) {
        try {
            $statusCode = 422;
            $return = [];
            $user = \App\Models\User::where('id', $request->user->id)->first();
            $requisition_items = json_decode($request->input('requisition_items'));
            $recommended_user = $request->input('recommended_user');
            $label = $request->input('label');
            $department = $request->input('department');

            $requisition = new Requisition;
            $requisition->user_id = $user->id;
            $requisition->company_id = $user->company;
            $requisition->billing_company_id = $user->company;
            $requisition->department = $department ?? $user->department;
            $requisition->recommended_user = $recommended_user;
            $requisition->label = $label;

            if ($requisition->save()) {
                foreach ($requisition_items as $val) {
                    $item = new RequisitionItem;
                    $item->requisition_id = $requisition->id;
                    $item->part_id = $val->part_id;
                    $item->unit = $val->unit;
                    $item->stock_in_hand = $val->stock_in_hand;
                    $item->qty = $val->qty;
                    $item->recommand_qty = $val->qty;
                    $item->audit_qty = $val->qty;
                    $item->final_qty = $val->qty;
                    $item->purchase_qty = is_null($val->part_id) ? 1 : 0;
                    $item->status = "Listed";
                    $item->remarks = $val->remarks;
                    $item->save();
                }
            }
            $return['data'] = $requisition;
            $statusCode = 200;
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
            $requisition = Requisition::find($id);

            if ($requisition) {
                $user = \App\Models\User::find($requisition->user_id);
                $requisition->requisition_by = $user->full_name;

                $company = \App\Models\Company::find($requisition->company_id);
                $requisition->company = $company->title;

                $department = \App\Models\Department::find($requisition->department);
                $requisition->department_title = $department->title;

                $billing_company = \App\Models\Company::find($requisition->billing_company_id);
                $requisition->billing_company = $billing_company->title;

                $requisition->total_qty = RequisitionItem::where('requisition_id', $requisition->id)->sum('purchase_qty');
                $requisition->total_amount = RequisitionItem::where('requisition_id', $requisition->id)->sum('total');

// SIGN PARTS
                $requisition->placed_by_sign = $this->getUserSign($requisition->placed_by);
                $requisition->placed_by_name = $this->getUserName($requisition->placed_by);

                $requisition->recommended_by_sign = $this->getUserSign($requisition->recommended_by);
                $requisition->recommended_by_name = $this->getUserName($requisition->recommended_by);

                $requisition->valuated_by_sign = $this->getUserSign($requisition->valuated_by);
                $requisition->valuated_by_name = $this->getUserName($requisition->valuated_by);

                $requisition->checked_by_sign = $this->getUserSign($requisition->checked_by);
                $requisition->checked_by_name = $this->getUserName($requisition->checked_by);

                $requisition->rejected_by_sign = $this->getUserSign($requisition->rejected_by);
                $requisition->rejected_by_name = $this->getUserName($requisition->rejected_by);

                $requisition->approved_by_sign = $this->getUserSign($requisition->approved_by);
                $requisition->approved_by_name = $this->getUserName($requisition->approved_by);

                $requisition->finalized_by_sign = $this->getUserSign($requisition->finalized_by);
                $requisition->finalized_by_name = $this->getUserName($requisition->finalized_by);

                $requisition_items = RequisitionItem::where('requisition_id', $requisition->id)->get();
                foreach ($requisition_items as $val) {
                    $part = $val->part_id ? Part::find($val->part_id) : null;
                    if (!$val->part_id) {
                        $val->is_service_charge = true;
                        $val->part_name = 'SERVICE CHARGE';
                    } else {
                        $val->is_service_charge = false;
                        $val->part_name = $part ? $part->title : '';
                    }

                    $val->requisition_number = $requisition->requisition_number;

//                    $val->left_purchase_qty = $val->final_qty - $val->purchase_qty;

                    $received = \App\Models\SubStoreReceive::where('requisition_id', $requisition->id)
                            ->where('requisition_item_id', $val->id)
                            ->sum('qty');
                    $val->received_qty = $received;
                    $val->left_received_qty = $val->final_qty - $val->received_qty;
                }
                $requisition->requisition_items = $requisition_items;
                $return['data'] = $requisition;
                $statusCode = 200;
                $return['status'] = 'success';
            } else {
                $return['status'] = 'error';
            }
            return $this->response($return, $statusCode);
        } catch (\Throwable $ex) {
            return $this->error(['status' => "error", 'main_error_message' => $ex->getMessage(), 'Error At Line No-' => $ex->getLine()]);
        }
    }

    private function getUserSign($userId) {
        $signUser = \App\Models\User::find($userId);
        if ($signUser) {
            return url('') . '/signs/' . $signUser->sign;
        }
        return null;
    }

    private function getUserName($userId) {
        $user = \App\Models\User::find($userId);
        return $user ? $user->full_name : "N/A";
    }

    public function toggle_status(Request $request) {
        try {
            $statusCode = 422;
            $return = [];
            $user = \App\Models\User::find($request->user->id);
            $id = $request->input('id');
            $requisition = Requisition::find($id);
            $status = $request->input('status');

            if ($requisition) {
                if ($status == "Placed") {
                    $requisition->placed_by = $user->id;
                    $requisition->placed_at = date('Y-m-d H:i:s');
                    $requisition->rejected_by = 0;
                    $requisition->rejected_at = '';
                    //notification

                    $notification = new \App\Models\Notification;
                    $notification->title = "Purchase Requisition Placed By " . $user->full_name;
                    $notification->receiver = $requisition->recommended_user;
                    $notification->url = "/requisitions-details/" . $requisition->id;
                    $notification->description = "Please Take Necessary Action";
                    $notification->is_read = 0;
                    $notification->save();
                } else if ($status == "Recommended") {
                    $requisition->recommended_by = $user->id;
                    $requisition->recommended_at = date('Y-m-d H:i:s');
                    $requisition->rejected_by = 0;
                    $requisition->rejected_at = '';

                    $notify_users = \App\Models\User::where('company', $requisition->company_id)->where('department', 27)->get();
                    if ($notify_users->isNotEmpty()) {
                        foreach ($notify_users as $notify_user) {
                            $notification = new \App\Models\Notification;
                            $notification->title = "Requisition Recommended By " . $user->full_name;
                            $notification->receiver = $notify_user->id;
                            $notification->url = "/requisitions-details/" . $requisition->id;
                            $notification->description = "Please Take Necessary Action";
                            $notification->is_read = 0;
                            $notification->save();
                        }
                    }
                } else if ($status == "Valuated") {
                    $requisition->valuated_by = $user->id;
                    $requisition->valuated_at = date('Y-m-d H:i:s');
                    $requisition->rejected_by = 0;
                    $requisition->rejected_at = '';

                    $notify_users = \App\Models\User::where('company', $requisition->company_id)->where('department', 5)->get();
                    if ($notify_users->isNotEmpty()) {
                        foreach ($notify_users as $notify_user) {
                            $notification = new \App\Models\Notification;
                            $notification->title = "Requisition Valuated By " . $user->full_name;
                            $notification->receiver = $notify_user->id;
                            $notification->url = "/requisitions-details/" . $requisition->id;
                            $notification->description = "Please Take Necessary Action";
                            $notification->is_read = 0;
                            $notification->save();
                        }
                    }
                } else if ($status == "Checked") {
                    $requisition->checked_by = $user->id;
                    $requisition->checked_at = date('Y-m-d H:i:s');
                    $requisition->rejected_by = 0;
                    $requisition->rejected_at = '';
                    $notify_users = \App\Models\User::where('company', $requisition->company_id)->whereIn('designation', [26, 29])->get();
                    if ($notify_users->isNotEmpty()) {
                        foreach ($notify_users as $notify_user) {
                            $notification = new \App\Models\Notification;
                            $notification->title = "Requisition Audited By " . $user->full_name;
                            $notification->receiver = $notify_user->id;
                            $notification->url = "/requisitions-details/" . $requisition->id;
                            $notification->description = "Please Take Necessary Action";
                            $notification->is_read = 0;
                            $notification->save();
                        }
                    }
                } else if ($status == "Finalized") {
                    $requisition->finalized_by = $user->id;
                    $requisition->finalized_at = date('Y-m-d H:i:s');
                    $requisition->rejected_by = 0;
                    $requisition->rejected_at = '';

                    $notification = new \App\Models\Notification;
                    $notification->title = "Requisition Finalized By " . $user->full_name;
                    $notification->receiver = $requisition->valuated_by;
                    $notification->url = "/requisitions-details/" . $requisition->id;
                    $notification->description = "Please Purchase and insert purchase rate";
                    $notification->is_read = 0;
                    $notification->save();
                } else if ($status == "Approved") {
                    $requisition->approved_by = $user->id;
                    $requisition->approved_at = date('Y-m-d H:i:s');
                    $requisition->rejected_by = 0;
                    $requisition->rejected_at = '';
                } else if ($status == "Rejected") {
                    $requisition->placed_by = 0;
                    $requisition->recommended_by = 0;
                    $requisition->checked_by = 0;
                    $requisition->finalized_by = 0;
                    $requisition->rejected_by = $user->id;
                    $requisition->rejected_at = date('Y-m-d H:i:s');
                    $requisition->placed_at = '';
                    $requisition->recommended_at = '';
                    $requisition->checked_at = '';
                    $requisition->finalized_at = '';

                    $notification = new \App\Models\Notification;
                    $notification->title = "Requisition Rejected By " . $user->full_name;
                    $notification->receiver = $requisition->user_id;
                    $notification->url = "/requisitions-details/" . $requisition->id;
                    $notification->description = "Please Take Necessary Action";
                    $notification->is_read = 0;
                    $notification->save();
                }

                $requisition->status = $status;
                $requisition->save();

                $return['data'] = $requisition;
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
            $recommended_user = $request->input('recommended_user');
            $label = $request->input('label');
            $requisition = Requisition::findOrFail($id);
            $requisition->recommended_user = $recommended_user;
            $requisition->label = $label;
            $requisition->department = $request->input('department');

            if ($requisition->save()) {
                RequisitionItem::where('requisition_id', $requisition->id)->delete();
                $requisition_items = json_decode($request->input('requisition_items'));
                foreach ($requisition_items as $val) {
                    $item = new RequisitionItem;
                    $item->requisition_id = $requisition->id;
                    $item->part_id = $val->part_id;
                    $item->unit = $val->unit;
                    $item->stock_in_hand = $val->stock_in_hand;
                    $item->qty = $val->qty;
                    $item->recommand_qty = $val->qty;
                    $item->audit_qty = $val->qty;
                    $item->final_qty = $val->qty;
                    $item->purchase_qty = 0;
//                    $item->status = "Pending";
                    $item->remarks = $val->remarks;
                    $item->save();
                }
            }
            $return['data'] = $requisition;
            $statusCode = 200;
            $return['status'] = 'success';

            return $this->response($return, $statusCode);
        } catch (\Throwable $ex) {
            return $this->error(['status' => "error", 'main_error_message' => $ex->getMessage()]);
        }
    }

    public function revise(Request $request) {
        try {
            $statusCode = 422;
            $return = [];
            $id = $request->input('id');
            $requisition = Requisition::findOrFail($id);

            if ($requisition) {
                // Loop through each item in the request
                $requisition_items = json_decode($request->input('requisition_items'));
                foreach ($requisition_items as $val) {
                    // Find the existing item by ID
                    $existing_item = RequisitionItem::find($val->id);
                    if ($existing_item) {
                        $existing_item->recommand_qty = $val->recommand_qty;
                        $existing_item->audit_qty = $val->audit_qty;
                        $existing_item->final_qty = $val->final_qty;
                        $existing_item->purchase_qty = $val->purchase_qty;
                        $existing_item->rate = $val->rate;
                        $existing_item->final_rate = $val->final_rate;
                        $existing_item->total = $val->total;

                        // Update status based on purchase_qty
                        if ($existing_item->purchase_qty == 0) {
                            $existing_item->status = 'Listed';
                        } elseif ($existing_item->purchase_qty > 0 && $existing_item->final_qty != $existing_item->purchase_qty) {
                            $existing_item->status = 'Pending';
                        } elseif ($existing_item->final_qty == $existing_item->purchase_qty) {
                            $existing_item->status = 'Purchased';
                        }
                        $existing_item->save();
                    }
                }
            }

            $return['data'] = $requisition;
            $statusCode = 200;
            $return['status'] = 'success';

            return $this->response($return, $statusCode);
        } catch (\Throwable $ex) {
            return $this->error(['status' => "error", 'main_error_message' => $ex->getMessage()]);
        }
    }

    public function single_requisition_item(Request $request) {
        try {
            $statusCode = 422;
            $return = [];
            $id = $request->input('id');
            $item = RequisitionItem::where('id', $id)->first();
            if ($item) {
                $requisition = Requisition::where('id', $item->requisition_id)->first();
                $item->company_id = $requisition->company_id;
                $item->requsition_user_id = $requisition->user_id;
                $part = Part::where('id', $item->part_id)->first();
                $item->part_name = $part->title;
//                $item->left_purchase_qty = $item->final_qty - $item->purchase_qty;
                $received = \App\Models\SubStoreReceive::where('requisition_id', $requisition->id)
                        ->where('requisition_item_id', $item->id)
                        ->sum('qty');
                $item->received_qty = $received;
                $item->left_received_qty = $item->final_qty - $item->received_qty;
                $item->supplier_id = '';
            }
            $return['data'] = $item;

            $statusCode = 200;
            $return['status'] = 'success';

            return $this->response($return, $statusCode);
        } catch (\Throwable $ex) {
            return $this->error(['status' => "error", 'main_error_message' => $ex->getMessage()]);
        }
    }

    public function pending_purchase(Request $request) {
        try {
            // Get the authenticated user
            $user = \App\Models\User::find($request->user->id);

            // Fetch requisitions based on user and status
            $requisitions = Requisition::where('status', 'Finalized')
                    ->where(function ($query) use ($user) {
                        $query->where('placed_by', $user->id)
                                ->orWhere('recommended_by', $user->id)
                                ->orWhere('valuated_by', $user->id)
                                ->orWhere('checked_by', $user->id)
                                ->orWhere('finalized_by', $user->id);
                    })
                    ->orderBy('created_at', 'desc')
                    ->pluck('id');

            // Fetch pending purchase items related to the requisitions
            $requisition_items = RequisitionItem::whereIn('requisition_id', $requisitions)
                    ->whereIn('status', ['Listed', 'Pending'])
                    ->paginate(100);

            // Add additional information to each item
            foreach ($requisition_items as $item) {
                $requisition = Requisition::find($item->requisition_id);
                $requisition_by = \App\Models\User::find($requisition->user_id);
                $item->requisition_number = $requisition->requisition_number;
                $item->user = $requisition_by->full_name;
                $part = Part::find($item->part_id);
                $item->part_name = $part->title;

                $received = \App\Models\SubStoreReceive::where('requisition_id', $item->requisition_id)
                        ->where('requisition_item_id', $item->id)
                        ->sum('qty');
                $item->received_qty = $received;
                $item->left_received_qty = $item->final_qty - $item->received_qty;
                $item->left_purchase_qty = $item->final_qty - $item->purchase_qty;
            }

            return response()->json([
                        'status' => 'success',
                        'requisitions' => $requisition_items,
                            ], 200);
        } catch (\Throwable $ex) {
            return response()->json(['status' => 'error', 'main_error_message' => $ex->getMessage()], 500);
        }
    }
}
