<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Requisition;
use App\Models\RequisitionItem;
use Illuminate\Support\Facades\Validator;
use App\Models\Part;
use App\Models\SubStore;
use Carbon\Carbon;
use App\Models\SubStoreIssue;
use App\Models\SubStoreReceive;
use App\Models\PartRequest;

class SubstorePowerController extends Controller {

//    public function reset_balance_operation(Request $request) {
//
//        $subStores = SubStore::with(['receives', 'issues'])->get();
//
//        foreach ($subStores as $subStore) {
//            $totalReceives = $subStore->receives->sum('qty');
//            $totalIssues = $subStore->issues->sum('qty');
//
//            // Calculate opening balance
//            $openingBalance = $subStore->qty - $totalReceives + $totalIssues;
//
//            // Prevent negative opening balance
//            $subStore->opening_balance = max(0, $openingBalance);
//
//            // Optionally, adjust qty if you want current stock to match reality
//            // $subStore->qty = max(0, $subStore->qty);
//
//            $subStore->save();
//        }
//    }

    public function reset_balance_operation(Request $request) {
        $subStores = SubStore::with(['receives', 'issues'])->get();

        foreach ($subStores as $subStore) {

            $totalReceives = $subStore->receives->sum('qty');
            $totalIssues = $subStore->issues->sum('qty');

            // Calculate normally
            $openingBalance = $totalIssues - $totalReceives + $subStore->qty;

            // If negative, auto-adjust qty
            if ($openingBalance < 0) {
                $adjustment = abs($openingBalance);

                // Increase qty so opening_balance becomes 0
                $subStore->qty += $adjustment;

                $openingBalance = 0;
            }

            $subStore->opening_balance = $openingBalance;
            $subStore->save();
        }
    }

    public function index(Request $request) {
        try {
            $statusCode = 422;
            $return = [];

            $type = $request->input('type');
            $company_id = $request->input('company_id');
            $item_id = $request->input('item_id');
            $search = $request->input('search');

            $currentMonth = now()->month;
            $currentYear = now()->year;

            // Base query with eager loading
            $query = SubStore::with([
                'part',
                'company',
                'receives' => fn($q) => $q->select('id', 'substore_id', 'part_id', 'qty', 'receive_date', 'created_at'),
                'issues' => fn($q) => $q->select('id', 'substore_id', 'part_id', 'qty', 'issue_date', 'created_at'),
            ]);

            // Filters
            if ($company_id)
                $query->where('company_id', $company_id);
            if ($item_id)
                $query->where('id', $item_id);
            if ($search) {
                $query->whereHas('part', fn($q) => $q->where('title', 'LIKE', "%{$search}%"));
            }
            if ($type) {
                $query->whereHas('part', fn($q) => $q->where('type', $type));
            }

            $substores_wo_pagination = $query->get();
            $substores = $query->paginate(200);

            foreach ($substores as $substore) {
                $receives = $substore->receives;
                $issues = $substore->issues;

                // Total receives & issues
                $substore->total_receives = $receives->sum('qty');
                $substore->total_issues = $issues->sum('qty');

                // Current month receives & issues
                $substore->current_month_receives = $receives
                        ->where('part_id', $substore->part_id)
                        ->where('created_at', '>=', now()->startOfMonth())
                        ->where('created_at', '<=', now()->endOfMonth())
                        ->sum('qty');

                $substore->current_month_issues = $issues
                        ->where('part_id', $substore->part_id)
                        ->where('issue_date', '>=', now()->startOfMonth())
                        ->where('issue_date', '<=', now()->endOfMonth())
                        ->sum('qty');

                // Last purchase
                $last_receive = $receives->where('part_id', $substore->part_id)
                        ->sortByDesc('created_at')
                        ->first();

                $substore->last_purchase_qty = $last_receive->qty ?? 'N/A';
                $substore->last_purchase_date = $last_receive ? Carbon::parse($last_receive->receive_date)->format('M j, Y') : 'N/A';
            }

            // Add part & company info for non-paginated data
            foreach ($substores_wo_pagination as $substore) {
                $part = $substore->part;
                if ($part) {
                    $substore->part_name = $part->title;
                    $substore->min_balance = $part->min_balance;
                    $substore->unit = $part->unit;
                    $substore->type = $part->type;
                    $substore->image_source = url('') . '/parts/' . $part->photo;
                }

                $company = $substore->company;
                $substore->company = $company->title ?? null;
            }

            $return['substores'] = $substores;
            $return['substores_wo_pagination'] = $substores_wo_pagination;
            $statusCode = 200;

            return $this->response($return, $statusCode);
        } catch (\Throwable $ex) {
            return $this->error([
                        'status' => 'error',
                        'main_error_message' => $ex->getMessage()
            ]);
        }
    }

    public function requisitions(Request $request) {
        try {
            $statusCode = 422;
            $return = [];

            // Get inputs from the request
            $period = $request->input('period');
            $year = $request->input('year');
            $month = $request->input('month');
            $from_date = $request->input('from_date');
            $to_date = $request->input('to_date');
            $company_id = $request->input('company_id');
            $department = $request->input('department');
            $status = $request->input('status');
            $user_id = $request->input('user_id');
            $search = $request->input('search');

            // Base query
            $query = Requisition::query();

            // Apply date filters based on the period
            if ($period == "Monthly") {
                $query->whereYear('created_at', $year)
                        ->whereMonth('created_at', $month);
            } elseif ($period == "Yearly") {
                $query->whereYear('created_at', $year);
            } elseif ($period == "Custom") {
                $from_date = date('Y-m-d', strtotime($from_date));
                $to_date = date('Y-m-d', strtotime($to_date . ' +1 day')); // Include end of day
                $query->whereBetween('created_at', [$from_date, $to_date]);
            } else {
                $currentYear = date('Y');
                $currentMonth = date('m');
                $query->whereYear('created_at', $currentYear)
                        ->whereMonth('created_at', $currentMonth);
            }

            // Apply search filter
            if ($search) {
                $query->where('title', 'LIKE', "%{$search}%");
            }

            // Apply other filters
            if ($company_id) {
                $query->where('company_id', $company_id);
            }
            if ($department) {
                $query->where('department', $department);
            }
            if ($status) {
                $query->where('status', $status);
            }
            if ($user_id) {
                $query->where('user_id', $user_id);
            }

            // Final ordering and pagination
            $requisitions = $query->orderBy('created_at', 'desc')->paginate(100);

            foreach ($requisitions as $requisition) {
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
                $requisition->recommended_by_sign = $this->getUserSign($requisition->recommended_by);
                $requisition->checked_by_sign = $this->getUserSign($requisition->checked_by);
                $requisition->rejected_by_sign = $this->getUserSign($requisition->rejected_by);
                $requisition->approved_by_sign = $this->getUserSign($requisition->approved_by);
                $requisition->finalized_by_sign = $this->getUserSign($requisition->finalized_by);

                $total_items = RequisitionItem::where('requisition_id', $requisition->id)->count();
                $total_purchesed_items = RequisitionItem::where('requisition_id', $requisition->id)->whereIn('status', ['Purchased', 'Inhoused'])->count();
                $total_partial_purchased_items = RequisitionItem::where('requisition_id', $requisition->id)->where('status', 'Pending')->count();
                $total_not_purchased = RequisitionItem::where('requisition_id', $requisition->id)->where('status', 'Listed')->count();
                $requisition->total_items = $total_items;
                $requisition->purchesed_items = $total_purchesed_items;
                $requisition->partial_purchased = $total_partial_purchased_items;
                $requisition->left_purchase_items = $total_not_purchased;
                if ($total_items > 0) {
                    $requisition->purchase_percentage = number_format(($total_purchesed_items / $total_items) * 100, 2);
                    $requisition->partial_purchase_percentage = number_format(($total_partial_purchased_items / $total_items) * 100, 2);
                } else {
                    $requisition->purchase_percentage = number_format(0, 2); // To handle division by zero in case total_items is 0
                    $requisition->partial_purchase_percentage = number_format(0, 2);
                }
                $requisition_items = RequisitionItem::where('requisition_id', $requisition->id)->get();
                foreach ($requisition_items as $val) {
                    $part = Part::find($val->part_id);
                    if ($part) {
                        $val->part_name = $part->title;
                    } else {
                        $val->part_name = 'N/A';
                    }

                    $val->requisition_number = $requisition->requisition_number;
                    $val->left_purchase_qty = $val->final_qty - $val->purchase_qty;

                    $received = \App\Models\SubStoreReceive::where('requisition_id', $requisition->id)
                            ->where('requisition_item_id', $val->id)
                            ->sum('qty');
                    $val->received_qty = $received;
                    $val->left_received_qty = $val->purchase_qty - $val->received_qty;
                }
                $requisition->requisition_items = $requisition_items;
            }

            $return['requisitions'] = $requisitions;
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

    private function getUserSign($userId) {
        $signUser = \App\Models\User::find($userId);
        if ($signUser) {
            return url('') . '/signs/' . $signUser->sign;
        }
        return null;
    }

    public function parts(Request $request) {
        try {
            $statusCode = 422;
            $return = [];
            // Get inputs from the request
            $company_id = $request->input('company_id');
            $type = $request->input('type');
            $user_id = $request->input('user_id');
            $search = $request->input('search');
            $item_id = $request->input('item_id');

            // Base query
            $query = Part::query();

            // Apply search filter
            if ($search) {
                $query->where('title', 'LIKE', "%{$search}%");
            }
            // Apply other filters
            if ($company_id) {
                $query->where('company_id', $company_id);
            }

            if ($item_id) {
                $query->where('id', $item_id);
            }
            if ($type) {
                $query->where('type', $type);
            }

            if ($user_id) {
                $query->where('user_id', $user_id);
            }

            // Final ordering and pagination
            $parts = $query->orderBy('created_at', 'desc')->paginate(200);

            foreach ($parts as $val) {
                $user = \App\Models\User::find($val->user_id);
                $val->user = $user->full_name;
                $company = \App\Models\Company::find($val->company_id);
                $val->company = $company->title;
                $val->image_source = url('') . '/parts/' . $val->photo;
            }
            $return['parts'] = $parts;
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

    public function receives(Request $request) {
        try {
            $statusCode = 422;
            $return = [];

//            $validator = Validator::make($request->all(), [
//                        'period' => 'required',
//                        'year' => 'required_if:period,Monthly,Yearly|nullable',
//                        'month' => 'required_if:period,Monthly|nullable',
//                        'from_date' => 'required_if:period,Custom|nullable',
//                        'to_date' => 'required_if:period,Custom|nullable',
//                        'type' => 'nullable',
//                        'supplier_id' => 'nullable',
//                        'item_id' => 'nullable',
//                        'company_id' => 'nullable',
//            ]);
//
//            if ($validator->fails()) {
//                return response()->json(['errors' => $validator->errors()], 422);
//            }
            // inputs from frontend
            $company_id = $request->input('company_id');
            $period = $request->input('period');
            $year = $request->input('year');
            $month = $request->input('month');
            $from_date = $request->input('from_date');
            $to_date = $request->input('to_date');
            $type = $request->input('type');
            $supplier_id = $request->input('supplier_id');
            $item_id = $request->input('item_id');

            //QUERY
            $query = SubStoreReceive::query();

            if ($company_id) {
                $query->where('company_id', $company_id);
            }

            if ($period == "Monthly") {
                $query->whereYear('created_at', $year)
                        ->whereMonth('created_at', $month);
            } else if ($period == "Yearly") {
                $query->whereYear('created_at', $year);
            } else if ($period == "Custom") {
                $from_date = date('Y-m-d', strtotime($from_date));
                $to_date = date('Y-m-d', strtotime($to_date . ' +1 day')); // Include end of day
                $query->whereBetween('created_at', [$from_date, $to_date]);
            } else {
                $currentYear = date('Y');
                $currentMonth = date('m');
                $query->whereYear('created_at', $currentYear)
                        ->whereMonth('created_at', $currentMonth);
            }


            if ($item_id) {
                $query->where('substore_id', $item_id);
            }
            if ($supplier_id) {
                $query->where('supplier_id', $supplier_id);
            }

            if ($type) {
                $query->whereHas('part', function ($q) use ($type) {
                    $q->where('type', $type);
                });
            }

            $receives = $query->orderBy('created_at', 'desc')->paginate(200);

            foreach ($receives as $val) {
                $requisition = \App\Models\Requisition::where('id', $val->requisition_id)->first();
                $val->requisition_number = $requisition->requisition_number;
                $requisition_item = \App\Models\RequisitionItem::where('id', $val->requisition_item_id)->first();
                $val->rate = $requisition_item->final_rate;
                $val->total = $val->qty * $val->rate;
                $received_by = \App\Models\User::where('id', $val->user_id)->first();
                $val->user = $received_by->full_name;
                $part = \App\Models\Part::where('id', $val->part_id)->first();
                $val->part_name = $part->title;
                $val->unit = $part->unit;
                $val->type = $part->type;
                $supplier = \App\Models\Supplier::where('id', $val->supplier_id)->first();
                $val->supplier_name = $supplier->company_name ?? "N/A";
            }
            $return['receives'] = $receives;
            $return['status'] = "success";
            $statusCode = 200;
            return $this->response($return, $statusCode);
        } catch (\Throwable $ex) {
            return $this->error(['status' => 'error', 'main_error_message' => $ex->getMessage()]);
        }
    }

    public function issues(Request $request) {
        try {
            $statusCode = 422;
            $return = [];

//            $validator = Validator::make($request->all(), [
//                        'period' => 'required',
//                        'year' => 'required_if:period,Monthly,Yearly|nullable',
//                        'month' => 'required_if:period,Monthly|nullable',
//                        'from_date' => 'required_if:period,Custom|nullable',
//                        'to_date' => 'required_if:period,Custom|nullable',
//                        'type' => 'nullable',
//                        'employee_id' => 'nullable',
//                        'item_id' => 'nullable',
//                        'issue_type' => 'nullable',
//                        'company_id' => 'nullable',
//            ]);
//
//            if ($validator->fails()) {
//                return response()->json(['errors' => $validator->errors()], 422);
//            }
            // inputs from frontend
            $company_id = $request->input('company_id');
            $period = $request->input('period');
            $year = $request->input('year');
            $month = $request->input('month');
            $from_date = $request->input('from_date');
            $to_date = $request->input('to_date');
            $type = $request->input('type');
            $employee_id = $request->input('employee_id');
            $issue_type = $request->input('issue_type');
            $item_id = $request->input('item_id');

            //QUERY
            $query = SubStoreIssue::query();

            if ($company_id) {
                $query->where('company_id', $company_id);
            }

            if ($period == "Monthly") {
                $query->whereYear('issue_date', $year)
                        ->whereMonth('issue_date', $month);
            } else if ($period == "Yearly") {
                $query->whereYear('issue_date', $year);
            } else if ($period == "Custom") {
                $from_date = date('Y-m-d', strtotime($from_date));
                $to_date = date('Y-m-d', strtotime($to_date . ' +1 day')); // Include end of day
                $query->whereBetween('issue_date', [$from_date, $to_date]);
            } else {
                $currentYear = date('Y');
                $currentMonth = date('m');
                $query->whereYear('issue_date', $currentYear)
                        ->whereMonth('issue_date', $currentMonth);
            }

            if ($issue_type) {
                $query->where('issue_type', $issue_type);
            }

            if ($item_id) {
                $query->where('substore_id', $item_id);
            }
            if ($employee_id) {
                $query->where('issue_to', $employee_id);
            }

            if ($type) {
                $query->whereHas('part', function ($q) use ($type) {
                    $q->where('type', $type);
                });
            }

            $issues = $query->orderBy('issue_date', 'desc')->paginate(200);

            foreach ($issues as $val) {
                $issue_by = \App\Models\User::where('id', $val->user_id)->first();
                $val->user = $issue_by->full_name;
                if ($val->issue_type == "Self") {
                    $issue_to_user = \App\Models\User::where('id', $val->issue_to)->first();
                    $val->issue_to_show = $issue_to_user->full_name;
                } else if ($val->issue_type == "Sister-Factory") {
                    $issue_to_company = \App\Models\Company::where('id', $val->issuing_company)->first();
                    $val->issue_to_show = $issue_to_company->title;
                }

                $company = \App\Models\Company::find($val->company_id);
                $val->company = $company->title;

                $part_request = PartRequest::where('id', $val->request_id)->first();
                $val->request_number = $part_request ? $part_request->request_number : 'N/A';
                $part = \App\Models\Part::where('id', $val->part_id)->first();
                $val->part_name = $part->title;
                $val->unit = $part->unit;
                $val->type = $part->type;
            }
            $return['issues'] = $issues;
            $return['status'] = "success";
            $statusCode = 200;
            return $this->response($return, $statusCode);
        } catch (\Throwable $ex) {
            return $this->error(['status' => 'error', 'main_error_message' => $ex->getMessage()]);
        }
    }
}
