<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\SubStore;
use App\Models\SubStoreReceive;
use App\Models\SubStoreIssue;
use App\Models\PartRequest;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\DB;
use App\Models\Team;
use Illuminate\Support\Facades\Mail;
use App\Mail\SubstoreReportMail;
use App\Models\User;
use App\Models\RequisitionItem;
use App\Models\Requisition;
use App\Models\Part;
use Carbon\Carbon;

class SubStoreController extends Controller {

    public function index(Request $request) {
        try {
            $statusCode = 422;
            $return = [];
            $type = $request->input('type');
            $user = \App\Models\User::find($request->user->id);

            $query = SubStore::where('company_id', $user->company);

            // Add search filter
            if ($request->has('search') && !empty($request->search)) {
                $search = $request->search;
                $query->where(function ($q) use ($search) {
                    $q->whereHas('part', function ($q) use ($search) {
                        $q->where('title', 'LIKE', "%{$search}%");
                    });
                });
            }


            $access = \App\Models\SubStoreAccess::where('user_id', $user->id)->first();
            $accessAreas = explode(',', $access->area);

            if ($access) {
                // Add type filter
                if ($type) {
                    if (in_array($type, $accessAreas)) {
                        $query->whereHas('part', function ($q) use ($type) {
                            $q->where('type', $type);
                        });
                    } else {
                        // If the type is not in access areas, return no results
                        $query->whereRaw('1 = 0');
                    }
                } else {
                    $query->whereHas('part', function ($q) use ($accessAreas) {
                        $q->whereIn('type', $accessAreas);
                    });
                }
            }

            $substores = $query->paginate(200);
            // Current month and year


            foreach ($substores as $val) {
                $part = \App\Models\Part::where('id', $val->part_id)->first();
                $val->part_name = $part->title;
                $val->min_balance = $part->min_balance;
                $val->unit = $part->unit;
                $val->type = $part->type;
                $val->image_source = url('') . '/parts/' . $part->photo;
            }

            $company_wise = SubStore::where('company_id', $user->company)
                    ->whereHas('part', function ($query) use ($accessAreas) {
                        $query->whereIn('type', $accessAreas);
                    })
                    ->get();

            foreach ($company_wise as $val) {
                $part = \App\Models\Part::find($val->part_id);
                $val->part_name = $part->title;
                $val->unit = $part->unit;
                $val->image_source = url('') . '/parts/' . $part->photo;
            }
            $all_data = SubStore::all();
            foreach ($all_data as $val) {
                $part = \App\Models\Part::find($val->part_id);
                $val->part_name = $part->title;
                $val->unit = $part->unit;
            }
            $return['company_wise'] = $company_wise;
            $return['all_data'] = $all_data;
            $return['substores'] = $substores;
            $statusCode = 200;
            return $this->response($return, $statusCode);
        } catch (\Throwable $ex) {
            return $this->error(['status' => 'error', 'main_error_message' => $ex->getMessage()]);
        }
    }

    public function index_origin(Request $request) {
        try {
            $statusCode = 422;
            $return = [];
            $type = $request->input('type');
            $user = \App\Models\User::find($request->user->id);

            $query = SubStore::where('company_id', $user->company);

            // Add search filter
            if ($request->has('search') && !empty($request->search)) {
                $search = $request->search;
                $query->where(function ($q) use ($search) {
                    $q->whereHas('part', function ($q) use ($search) {
                        $q->where('title', 'LIKE', "%{$search}%");
                    });
                });
            }


            $access = \App\Models\SubStoreAccess::where('user_id', $user->id)->first();
            $accessAreas = explode(',', $access->area);

            if ($access) {
                // Add type filter
                if ($type) {
                    if (in_array($type, $accessAreas)) {
                        $query->whereHas('part', function ($q) use ($type) {
                            $q->where('type', $type);
                        });
                    } else {
                        // If the type is not in access areas, return no results
                        $query->whereRaw('1 = 0');
                    }
                } else {
                    $query->whereHas('part', function ($q) use ($accessAreas) {
                        $q->whereIn('type', $accessAreas);
                    });
                }
            }

            $substores = $query->paginate(200);
            // Current month and year
            $currentMonth = Carbon::now()->month;
            $currentYear = Carbon::now()->year;
            $startOfMonth = Carbon::now()->startOfMonth();

            foreach ($substores as $val) {
                $part = \App\Models\Part::where('id', $val->part_id)->first();
                $val->part_name = $part->title;
                $val->min_balance = $part->min_balance;
                $val->unit = $part->unit;
                $val->type = $part->type;
                $val->image_source = url('') . '/parts/' . $part->photo;
                $company = \App\Models\Company::where('id', $val->company_id)->first();
                $val->company = $company->title;

                // Calculate total receives and issues
                $total_receives = SubStoreReceive::where('substore_id', $val->id)->sum('qty');
                $total_issues = SubStoreIssue::where('substore_id', $val->id)->sum('qty');
                $val->total_receives = $total_receives;
                $val->total_issues = $total_issues;
                $transaction_balance = $total_receives - $total_issues;
                $opening_startting_bl = $val->qty - $transaction_balance;
                $val->opening_balance = $val->qty - $transaction_balance;

                // Calculate opening balance for the current month
                $total_receives_before_month = SubStoreReceive::where('substore_id', $val->id)
                        ->where('part_id', $val->part_id)
                        ->where('receive_date', '<', $startOfMonth)
                        ->sum('qty');

                $total_issues_before_month = SubStoreIssue::where('substore_id', $val->id)
                        ->where('part_id', $val->part_id)
                        ->where('issue_date', '<', $startOfMonth)
                        ->sum('qty');

                $opening_balance_current_month = $total_receives_before_month - $total_issues_before_month;

                // Calculate total receives and issues for the current month
                $total_receives_this_month = SubStoreReceive::where('substore_id', $val->id)
                        ->where('part_id', $val->part_id)
                        ->whereYear('receive_date', $currentYear)
                        ->whereMonth('receive_date', $currentMonth)
                        ->sum('qty');

                $total_issues_this_month = SubStoreIssue::where('substore_id', $val->id)
                        ->where('part_id', $val->part_id)
                        ->whereYear('issue_date', $currentYear)
                        ->whereMonth('issue_date', $currentMonth)
                        ->sum('qty');

                // Add current month data to the val object
                $val->current_month_opening_balance = $opening_balance_current_month + $opening_startting_bl;
                $val->current_month_receives = $total_receives_this_month;
                $val->current_month_issues = $total_issues_this_month;
                $val->current_month_total = $total_receives_this_month + $opening_balance_current_month + $opening_startting_bl;
                $val->current_month_balance = ($total_receives_this_month + $opening_balance_current_month + $opening_startting_bl) - $total_issues_this_month;
            }

            $company_wise = SubStore::where('company_id', $user->company)
                    ->whereHas('part', function ($query) use ($accessAreas) {
                        $query->whereIn('type', $accessAreas);
                    })
                    ->get();

            foreach ($company_wise as $val) {
                $part = \App\Models\Part::find($val->part_id);
                $val->part_name = $part->title;
                $val->unit = $part->unit;
                $val->image_source = url('') . '/parts/' . $part->photo;
            }




            $all_data = SubStore::all();
            foreach ($all_data as $val) {
                $part = \App\Models\Part::find($val->part_id);
                $val->part_name = $part->title;
                $val->unit = $part->unit;
            }
            $return['company_wise'] = $company_wise;
            $return['all_data'] = $all_data;
            $return['substores'] = $substores;
            $statusCode = 200;
            return $this->response($return, $statusCode);
        } catch (\Throwable $ex) {
            return $this->error(['status' => 'error', 'main_error_message' => $ex->getMessage()]);
        }
    }

    //START SUBSTORE REPORT
    public function make_report(Request $request) {
        try {
            $statusCode = 422;
            $return = [];

            $validator = Validator::make($request->all(), [
                'period' => 'required',
                'year' => 'required_if:period,Monthly,Yearly|nullable',
                'month' => 'required_if:period,Monthly|nullable',
                'from_date' => 'required_if:period,Custom|nullable',
                'to_date' => 'required_if:period,Custom|nullable',
                'type' => 'nullable',
                'item_id' => 'nullable',
            ]);

            if ($validator->fails()) {
                return response()->json(['errors' => $validator->errors()], 422);
            }

            // inputs from frontend
            $period = $request->input('period');
            $year = $request->input('year');
            $month = $request->input('month');
            $from_date = $request->input('from_date');
            $to_date = $request->input('to_date');
            $type = $request->input('type');
            $item_id = $request->input('item_id');

            $user = \App\Models\User::find($request->user->id);
            $company_id = $request->input('company_id') ? $request->input('company_id') : $user->company;
            $company = \App\Models\Company::find($company_id);
            $query = SubStore::where('company_id', $company_id);

            if ($item_id) {
                $query->where('id', $item_id);
            }

            if ($type) {
                $query->whereHas('part', function ($q) use ($type) {
                    $q->where('type', $type);
                });
            }

            $substores = $query->get();
            $report = [];

            $reportSummary = [];

            if ($period == "Monthly") {
                $report = $this->generateMonthlyReport($substores, $year, $month);
                $monthName = Carbon::createFromDate(null, $month, 1)->monthName; // Get month name
                $daysInMonth = Carbon::createFromDate($year, $month, 1)->daysInMonth;
                $reportSummary['report_type'] = "Monthly";
                $reportSummary['report_month'] = $monthName;
                $reportSummary['report_length'] = $daysInMonth;
                $reportSummary['report_year'] = $year;
            } else if ($period == "Yearly") {
                $report = $this->generateYearlyReport($substores, $year);
                $reportSummary['report_type'] = "Yearly";
                $reportSummary['report_year'] = $year;
                $reportSummary['report_length'] = 12;
            } else if ($period == "Custom") {
                $report = $this->generateCustomReport($substores, $from_date, $to_date);
                $fromDate = Carbon::createFromFormat('Y-m-d', $from_date)->format('jS M, Y'); // Format from_date
                $toDate = Carbon::createFromFormat('Y-m-d', $to_date)->format('jS M, Y'); // Format to_date
                $reportSummary['report_type'] = "Custom";
                $reportSummary['report_from_date'] = $fromDate;
                $reportSummary['report_to_date'] = $toDate;
            }
            $reportSummary['part_type'] = $type ?? 'All';
            $reportSummary['company_name'] = $company->title;

            $return['data'] = $report;
            $return['reportSummary'] = $reportSummary; // Add reportSummary to response
            $statusCode = 200;
            return $this->response($return, $statusCode);
        } catch (\Throwable $ex) {
            return $this->error(['status' => 'error', 'main_error_message' => $ex->getMessage()]);
        }
    }

    private function generateMonthlyReport($substores, $year, $month) {
        $report = [];

        // Create a Carbon instance for the selected month
        $selected_month = Carbon::createFromDate($year, $month, 1)->startOfMonth();

        foreach ($substores as $substore) {


            $total_receives = SubStoreReceive::where('substore_id', $substore->id)
                            ->where('part_id', $substore->part_id)->sum('qty');

            $total_issues = SubStoreIssue::where('substore_id', $substore->id)
                            ->where('part_id', $substore->part_id)->sum('qty');

            $transaction_balance = $total_receives - $total_issues;
            $startingBalance = $substore->qty - $transaction_balance;

            // Compute opening balance before the selected month
            $totalReceivesBeforeFrom = SubStoreReceive::where('substore_id', $substore->id)
                    ->where('part_id', $substore->part_id)
                    ->where('receive_date', '<', $selected_month)
                    ->sum('qty');

            $totalIssuesBeforeFrom = SubStoreIssue::where('substore_id', $substore->id)
                    ->where('part_id', $substore->part_id)
                    ->where('issue_date', '<', $selected_month)
                    ->sum('qty');
            $openingBalance = $totalReceivesBeforeFrom - $totalIssuesBeforeFrom;

            // Compute total receives and issues for the selected month
            $total_receives_this_month = SubStoreReceive::where('substore_id', $substore->id)
                    ->where('part_id', $substore->part_id)
                    ->whereYear('receive_date', $year)
                    ->whereMonth('receive_date', $month)
                    ->sum('qty');

            $total_issues_this_month = SubStoreIssue::where('substore_id', $substore->id)
                    ->where('part_id', $substore->part_id)
                    ->whereYear('issue_date', $year)
                    ->whereMonth('issue_date', $month)
                    ->sum('qty');

            $daysInMonth = $selected_month->daysInMonth;
            $records = [];

            for ($day = 1; $day <= $daysInMonth; $day++) {
                $date = sprintf('%04d-%02d-%02d', $year, $month, $day);

                $daily_receive = SubStoreReceive::where('substore_id', $substore->id)
                        ->where('part_id', $substore->part_id)
                        ->whereDate('receive_date', $date)
                        ->sum('qty');

                $daily_issue = SubStoreIssue::where('substore_id', $substore->id)
                        ->where('part_id', $substore->part_id)
                        ->whereDate('issue_date', $date)
                        ->sum('qty');

                $records[] = [
                    'date' => $date,
                    'receives' => $daily_receive,
                    'issues' => $daily_issue,
                ];
            }

            $part = \App\Models\Part::where('id', $substore->part_id)->first();
            $report[] = [
                'item_title' => $part->title,
                'unit' => $part->unit,
                'type' => $part->type,
                'opening_balance' => $openingBalance + $startingBalance,
                'total_receives' => $total_receives_this_month,
                'total_issues' => $total_issues_this_month,
                'total' => $openingBalance + $startingBalance + $total_receives_this_month,
                'balance' => ($openingBalance + $startingBalance + $total_receives_this_month) - $total_issues_this_month,
                'records' => $records,
            ];
        }

        return $report;
    }

    private function generateYearlyReport($substores, $year) {
        $report = [];

        foreach ($substores as $substore) {
            // Initialize totals and opening balance
            $total_receives = SubStoreReceive::where('substore_id', $substore->id)
                            ->where('part_id', $substore->part_id)->sum('qty');

            $total_issues = SubStoreIssue::where('substore_id', $substore->id)
                            ->where('part_id', $substore->part_id)->sum('qty');

            $transaction_balance = $total_receives - $total_issues;
            $startingBalance = $substore->qty - $transaction_balance;

            // Compute opening balance before the selected year
            $totalReceivesBeforeFrom = SubStoreReceive::where('substore_id', $substore->id)
                    ->where('part_id', $substore->part_id)
                    ->where('receive_date', '<', Carbon::createFromDate($year, 1, 1))
                    ->sum('qty');

            $totalIssuesBeforeFrom = SubStoreIssue::where('substore_id', $substore->id)
                    ->where('part_id', $substore->part_id)
                    ->where('issue_date', '<', Carbon::createFromDate($year, 1, 1))
                    ->sum('qty');

            $openingBalance = $totalReceivesBeforeFrom - $totalIssuesBeforeFrom;

            $total_receives_this_year = SubStoreReceive::where('substore_id', $substore->id)
                    ->where('part_id', $substore->part_id)
                    ->whereYear('receive_date', $year)
                    ->sum('qty');

            $total_issues_this_year = SubStoreIssue::where('substore_id', $substore->id)
                    ->where('part_id', $substore->part_id)
                    ->whereYear('issue_date', $year)
                    ->sum('qty');

            $records = [];

            for ($month = 1; $month <= 12; $month++) {
                $startDate = Carbon::createFromDate($year, $month, 1)->startOfMonth();
                $endDate = Carbon::createFromDate($year, $month, 1)->endOfMonth();

                $totalReceivesThisMonth = SubStoreReceive::where('substore_id', $substore->id)
                        ->where('part_id', $substore->part_id)
                        ->whereBetween('receive_date', [$startDate, $endDate])
                        ->sum('qty');

                $totalIssuesThisMonth = SubStoreIssue::where('substore_id', $substore->id)
                        ->where('part_id', $substore->part_id)
                        ->whereBetween('issue_date', [$startDate, $endDate])
                        ->sum('qty');

                $records[] = [
                    'month' => $month,
                    'receives' => $totalReceivesThisMonth,
                    'issues' => $totalIssuesThisMonth,
                ];
            }

            $part = \App\Models\Part::where('id', $substore->part_id)->first();
            $report[] = [
                'item_title' => $part->title,
                'unit' => $part->unit,
                'type' => $part->type,
                'opening_balance' => $openingBalance + $startingBalance,
                'total_receives' => $total_receives_this_year,
                'total_issues' => $total_issues_this_year,
                'total' => $openingBalance + $startingBalance + $total_receives_this_year,
                'balance' => ($openingBalance + $startingBalance + $total_receives_this_year) - $total_issues_this_year,
                'records' => $records,
            ];
        }

        return $report;
    }

    private function generateCustomReport($substores, $from_date, $to_date) {
        $report = [];

        foreach ($substores as $substore) {
            // Compute transaction balance and starting balance
            $total_receives = SubStoreReceive::where('substore_id', $substore->id)
                            ->where('part_id', $substore->part_id)->sum('qty');

            $total_issues = SubStoreIssue::where('substore_id', $substore->id)
                            ->where('part_id', $substore->part_id)->sum('qty');

            $transaction_balance = $total_receives - $total_issues;
            $startingBalance = $substore->qty - $transaction_balance;

            $from_date = date('Y-m-d', strtotime($from_date));
            $to_date = date('Y-m-d', strtotime($to_date . ' +1 day')); // Include end of day
            // Compute opening balance before the selected date range
            $totalReceivesBeforeFrom = SubStoreReceive::where('substore_id', $substore->id)
                    ->where('part_id', $substore->part_id)
                    ->where('receive_date', '<', $from_date)
                    ->sum('qty');

            $totalIssuesBeforeFrom = SubStoreIssue::where('substore_id', $substore->id)
                    ->where('part_id', $substore->part_id)
                    ->where('issue_date', '<', $from_date)
                    ->sum('qty');
            $openingBalance = $totalReceivesBeforeFrom - $totalIssuesBeforeFrom;

            // Compute total receives and issues within the selected date range
            $totalReceivesInRange = SubStoreReceive::where('substore_id', $substore->id)
                    ->where('part_id', $substore->part_id)
                    ->whereBetween('receive_date', [$from_date, $to_date])
                    ->sum('qty');

            $totalIssuesInRange = SubStoreIssue::where('substore_id', $substore->id)
                    ->where('part_id', $substore->part_id)
                    ->whereBetween('issue_date', [$from_date, $to_date])
                    ->sum('qty');

            $part = \App\Models\Part::where('id', $substore->part_id)->first();
            $report[] = [
                'item_title' => $part->title,
                'unit' => $part->unit,
                'type' => $part->type,
                'opening_balance' => $openingBalance + $startingBalance,
                'total_receives' => $totalReceivesInRange,
                'total_issues' => $totalIssuesInRange,
                'total' => $openingBalance + $startingBalance + $totalReceivesInRange,
                'balance' => ($openingBalance + $startingBalance + $totalReceivesInRange) - $totalIssuesInRange,
                'records' => [],
            ];
        }

        return $report;
    }

    //END SUBSTORE REPORT AREA
    public function open(Request $request) {
        try {
            $statusCode = 422;
            $return = [];

            $validator = Validator::make($request->all(), [
                'part_id' => 'required',
                'company_id' => 'required',
                'qty' => 'required',
            ]);

            if ($validator->fails()) {
                return response()->json(['errors' => $validator->errors()], 422);
            }

            $part_id = $request->input('part_id');
            $company_id = $request->input('company_id');
            $qty = $request->input('qty');

            $substore = SubStore::where('company_id', $company_id)
                    ->where('part_id', $part_id)
                    ->first();

            if ($substore) {
                $substore->increment('qty', $qty);
            } else {
                $substore = new SubStore;
                $substore->part_id = $part_id;
                $substore->company_id = $company_id;
                $substore->qty = $qty;
                $substore->save();
            }

            $return['data'] = $substore;
            $statusCode = 200;
            $return['status'] = 'success';
            return $this->response($return, $statusCode);
        } catch (\Throwable $ex) {
            return response()->json(['status' => 'error', 'main_error_message' => $ex->getMessage()], 500);
        }
    }

    public function revise_balance(Request $request) {
        try {
            $statusCode = 422;
            $return = [];

            $validator = Validator::make($request->all(), [
                'part_id' => 'required',
                'qty' => 'required',
            ]);

            if ($validator->fails()) {
                return response()->json(['errors' => $validator->errors()], 422);
            }

            $part_id = $request->input('part_id');
            $qty = $request->input('qty');

            $substore = SubStore::where('part_id', $part_id)->first();
            $substore->qty = $qty;
            $substore->save();
            $return['data'] = $substore;
            $statusCode = 200;
            $return['status'] = 'success';
            return $this->response($return, $statusCode);
        } catch (\Throwable $ex) {
            return response()->json(['status' => 'error', 'main_error_message' => $ex->getMessage()], 500);
        }
    }

    public function show(Request $request) {
        try {
            $statusCode = 422;
            $return = [];
            $id = $request->input('id');
            $substore = SubStore::find($id);
            if ($substore) {
                $part = \App\Models\Part::where('id', $substore->part_id)->first();
                $substore->part_name = $part->title;
                $substore->unit = $part->unit;
                $receives = SubStoreReceive::where('substore_id', $substore->id)->orderBy('receive_date', 'desc')->get();

                foreach ($receives as $val) {
                    $requisition = \App\Models\Requisition::where('id', $val->requisition_id)->first();
                    $val->requisition_number = $requisition->requisition_number;
                    $received_by = \App\Models\User::where('id', $val->user_id)->first();
                    $val->user = $received_by->full_name;
                    $part = \App\Models\Part::where('id', $val->part_id)->first();
                    $val->part_name = $part->title;
                    $val->unit = $part->unit;
                    $supplier = \App\Models\Supplier::where('id', $val->supplier_id)->first();
                    $val->supplier_name = $supplier->company_name ?? "N/A";
                }
                $substore->receives = $receives;
                $substore->total_received = $receives->sum('qty');

                $issues = SubStoreIssue::where('substore_id', $substore->id)->orderBy('issue_date', 'desc')->get();
                foreach ($issues as $val) {
                    $user = \App\Models\User::where('id', $val->user_id)->first();
                    $val->user = $user->full_name;
                    $val->challan_file = url('') . '/challan-copies/' . $val->challan_copy;

                    if ($val->issue_type == "Self") {
                        $issue_to_user = \App\Models\User::where('id', $val->issue_to)->first();
                        $val->issue_to_show = $issue_to_user->full_name;
                    } else if ($val->issue_type == "Sister-Factory") {
                        $issue_to_company = \App\Models\Company::where('id', $val->issuing_company)->first();
                        $val->issue_to_show = $issue_to_company->title;
                    }
                }

                $substore->issues = $issues;
                $substore->total_issued = $issues->sum('qty');

                $return['data'] = $substore;
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

    public function pending_receive(Request $request) {
        try {
            $statusCode = 422;
            $return = [];
            $user = User::find($request->user->id);

            $requisitions = Requisition::where('status', 'Finalized')
                    ->where('user_id', $user->id)
                    ->orderBy('created_at', 'desc')
                    ->pluck('id');

            // Fetch pending purchase items related to the requisitions
            $requisition_items = RequisitionItem::whereIn('requisition_id', $requisitions)
                            ->whereIn('status', ['Listed', 'Pending'])->get();
            // Processing each pending receive item
            foreach ($requisition_items as $item) {
                $requisition = Requisition::find($item->requisition_id);
                $requisition_by = User::find($requisition->user_id);
                $item->requisition_number = $requisition->requisition_number;
                $item->user = $requisition_by->full_name;
                $part = Part::find($item->part_id);
                if ($part) {
                    $item->part_name = $part->title;
                } else {
                    $item->part_name = 'N/A';
                }


                $received = SubStoreReceive::where('requisition_id', $item->requisition_id)
                        ->where('requisition_item_id', $item->id)
                        ->sum('qty');
                $item->received_qty = $received;
                $item->left_received_qty = $item->final_qty - $item->received_qty;
            }

            $return['data'] = $requisition_items;
            $return['status'] = 'success';
            $statusCode = 200;
            return response()->json($return, $statusCode);
        } catch (\Throwable $ex) {
            // Rollback transaction in case of error
            DB::rollBack();
            return response()->json(['status' => 'error', 'main_error_message' => $ex->getMessage()], 500);
        }
    }

    public function receive(Request $request) {
        try {
            $statusCode = 422;
            $return = [];

            $validator = Validator::make($request->all(), [
                'id' => 'required',
                'requisition_id' => 'required',
                'part_id' => 'required',
                'company_id' => 'required',
                'qty' => 'required',
                'receive_date' => [
                    'required',
                    'date',
                    'before_or_equal:today',
                    'after_or_equal:' . now()->subDays(20)->toDateString(),
                ],
                'receive_qty' => 'required',
                'supplier_id' => 'required',
                'challan_no' => 'required',
                'gate_pass' => 'required',
                'mrr_no' => 'required',
                'challan_copy' => 'nullable',
            ]);

            if ($validator->fails()) {
                return response()->json(['errors' => $validator->errors()], 422);
            }

            $user = \App\Models\User::find($request->user->id);
            $requisition_item_id = $request->input('id');
            $requisition_id = $request->input('requisition_id');
            $part_id = $request->input('part_id');
            $company_id = $request->input('company_id');
            $req_qty = $request->input('qty');
            $receive_qty = $request->input('receive_qty');
            //Receivecon
            $supplier_id = $request->input('supplier_id');
            $challan_no = $request->input('challan_no');
            $gate_pass = $request->input('gate_pass');
            $mrr_no = $request->input('mrr_no');
            $receive_date = $request->input('receive_date');

            $exist = SubStore::where('company_id', $company_id)
                    ->where('part_id', $part_id)
                    ->first();

            $already_received = SubStoreReceive::where('requisition_id', $requisition_id)
                    ->where('requisition_item_id', $requisition_item_id)
                    ->where('part_id', $part_id)
                    ->sum('qty');

            if ($exist) {
                if (($already_received + $receive_qty) <= $req_qty) {
                    $receive = new SubStoreReceive;
                    $receive->requisition_id = $requisition_id;
                    $receive->requisition_item_id = $requisition_item_id;
                    $receive->user_id = $user->id;
                    $receive->substore_id = $exist->id;
                    $receive->part_id = $part_id;
                    $receive->qty = $receive_qty;
                    $receive->company_id = $company_id;
                    //added on 04.06.24
                    $receive->supplier_id = $supplier_id;
                    $receive->challan_no = $challan_no;
                    $receive->mrr_no = $mrr_no;
                    $receive->gate_pass = $gate_pass;
                    $receive->receive_date = $receive_date;

                    if ($request->hasFile('challan_copy')) {
                        $challanCopy = $request->file('challan_copy');
                        $challanCopyName = time() . '_' . $challanCopy->getClientOriginalName();
                        $challanCopy->move(public_path('challan-copies'), $challanCopyName);
                        $receive->challan_copy = $challanCopyName;
                    } else {
                        $receive->challan_copy = '';
                    }
                    if ($receive->save()) {
                        $requisition_item = RequisitionItem::find($requisition_item_id);
                        $requisition_item->increment('purchase_qty', $receive->qty);
                        // Update status based on purchase_qty
                        if ($requisition_item->final_qty == $requisition_item->purchase_qty) {
                            $requisition_item->status = 'Inhoused';
                        } else {
                            $requisition_item->status = 'Pending';
                        }
                        $requisition_item->total = $requisition_item->final_rate * $requisition_item->purchase_qty;
                        $requisition_item->save();

                        $exist->increment('qty', $receive->qty);
                        $return['data'] = $receive;
                        $statusCode = 200;
                        $return['status'] = 'success';
                    } else {
                        $return['errors']['receive'] = 'Failed to save receive record.';
                    }
                } else {
                    $return['errors']['receive_qty'] = 'Trying to insert greater than requisition qty';
                }
            } else {
                if ($receive_qty <= $req_qty) {
                    $substore = new SubStore();
                    $substore->part_id = $part_id;
                    $substore->company_id = $company_id;
                    $substore->qty = $receive_qty;
                    if ($substore->save()) {
                        $receive = new SubStoreReceive;
                        $receive->requisition_id = $requisition_id;
                        $receive->requisition_item_id = $requisition_item_id;
                        $receive->user_id = $user->id;
                        $receive->substore_id = $substore->id;
                        $receive->company_id = $company_id;
                        $receive->part_id = $part_id;
                        $receive->qty = $receive_qty;
                        //added on 04.06.24
                        $receive->supplier_id = $supplier_id;
                        $receive->challan_no = $challan_no;
                        $receive->mrr_no = $mrr_no;
                        $receive->gate_pass = $gate_pass;
                        $receive->receive_date = $receive_date;

                        if ($request->hasFile('challan_copy')) {
                            $challanCopy = $request->file('challan_copy');
                            $challanCopyName = time() . '_' . $challanCopy->getClientOriginalName();
                            $challanCopy->move(public_path('challan-copies'), $challanCopyName);
                            $receive->challan_copy = $challanCopyName;
                        } else {
                            $receive->challan_copy = '';
                        }

                        if ($receive->save()) {
                            $requisition_item = RequisitionItem::find($requisition_item_id);
                            $requisition_item->increment('purchase_qty', $receive->qty);
                            // Update status based on purchase_qty
                            if ($requisition_item->final_qty == $requisition_item->purchase_qty) {
                                $requisition_item->status = 'Inhoused';
                            } else {
                                $requisition_item->status = 'Pending';
                            }
                            $requisition_item->total = $requisition_item->final_rate * $requisition_item->purchase_qty;
                            $requisition_item->save();
                            $return['data'] = $substore;
                            $statusCode = 200;
                            $return['status'] = 'success';
                        } else {
                            $return['errors']['saving_error'] = 'Failed to save receive record.';
                        }
                    } else {
                        $return['errors']['saving_error'] = 'Failed to save store record.';
                    }
                } else {
                    $return['errors']['receive_qty'] = 'Trying to insert greater than booking qty';
                }
            }



            $requisition_item = \App\Models\RequisitionItem::where('id', $requisition_item_id)->first();
            if ($requisition_item) {
                $total_receive_qty = SubStoreReceive::where('requisition_item_id', $requisition_item->id)->sum('qty');
                if ($requisition_item->final_qty == $total_receive_qty) {
                    $requisition_item->status = 'Inhoused';
                    $requisition_item->save();
                }
            }
            return $this->response($return, $statusCode);
        } catch (\Throwable $ex) {
            return $this->error(['status' => 'error', 'main_error_message' => $ex->getMessage()]);
        }
    }

    public function receive_undo(Request $request) {
        try {
            $statusCode = 422;
            $return = [];

            $id = $request->input('id');
            $receive = SubStoreReceive::find($id);

            if ($receive) {
                $created_at = $receive->created_at;
                $now = \Carbon\Carbon::now();
                $diffInHours = $now->diffInHours($created_at);

                if ($diffInHours <= 72) {
                    $substore = SubStore::find($receive->substore_id);
                    if ($substore) {
                        $substore->decrement('qty', $receive->qty);
                    }

                    if ($receive->delete()) {
                        $statusCode = 200;
                        $return['status'] = 'success';
                    } else {
                        $return['errors']['message'] = 'Failed to delete receive record.';
                    }
                } else {
                    $return['errors']['message'] = 'Undo operation is allowed only within 72 hours of record creation.';
                }
            } else {
                $return['errors']['message'] = 'Receive record not found.';
            }

            return response()->json($return, $statusCode);
        } catch (\Throwable $ex) {
            return $this->error(['status' => 'error', 'main_error_message' => $ex->getMessage()]);
        }
    }

    public function issue(Request $request) {
        try {
            $statusCode = 422;
            $return = [];

            $validator = Validator::make($request->all(), [
                'part_id' => 'required',
                'id' => 'required',
                'issue_type' => 'required',
                'issue_to' => $request->input('issue_type') === 'Self' ? 'required' : 'nullable',
                'line' => $request->input('issue_type') === 'Self' ? 'required' : 'nullable',
                'reference' => 'required',
                'issuing_company' => $request->input('issue_type') === 'Sister-Factory' ? 'required' : 'nullable',
                'company_id' => 'required',
                'remarks' => 'nullable',
                'qty' => 'required',
                'issue_qty' => 'required',
                'challan_copy' => 'nullable',
                'issue_date' => [
                    'required',
                    'date',
                    'before_or_equal:today',
                    'after_or_equal:' . now()->subDays(45)->toDateString(),
                ],
            ]);
            if ($validator->fails()) {
                return response()->json(['errors' => $validator->errors()], 422);
            }
            // Extract variables from the request

            $part_id = $request->input('part_id');
            $user_id = $request->user->id;
            $substore_id = $request->input('id');
            $issue_type = $request->input('issue_type');
            $issue_to = $request->input('issue_to');
            $line = $request->input('line');
            $reference = $request->input('reference');
            $issuing_company = $request->input('issuing_company');
            $company_id = $request->input('company_id');
            $remarks = $request->input('remarks');
            $qty = $request->input('qty');
            $issue_qty = $request->input('issue_qty');
            $issue_date = $request->input('issue_date');

            $substore = SubStore::find($substore_id);

            if ($issue_qty <= $substore->qty) {
                $issue = new SubStoreIssue();
                $issue->part_id = $part_id;
                $issue->user_id = $user_id;
                $issue->substore_id = $substore_id;
                if ($issue_type == "Self") {
                    $issue->issue_type = $issue_type;
                    $issue->issue_to = $issue_to;
                    $issue->issuing_company = 0;
                    $issue->line = $line;
                } else if ($issue_type == "Sister-Factory") {
                    $issue->issue_type = $issue_type;
                    $issue->issuing_company = $issuing_company;
                    $issue->issue_to = 0;
                    $issue->line = '';
                }

                $issue->qty = $issue_qty;
                $issue->company_id = $company_id;
                $issue->remarks = $remarks;
                $issue->reference = $reference;
                $issue->issue_date = $issue_date;

                if ($request->hasFile('challan_copy')) {
                    $challanCopy = $request->file('challan_copy');
                    $challanCopyName = time() . '_' . $challanCopy->getClientOriginalName();
                    $challanCopy->move(public_path('challan-copies'), $challanCopyName);
                    $issue->challan_copy = $challanCopyName;
                }

                if ($issue->save()) {
                    $substore = SubStore::find($issue->substore_id);
                    $substore->decrement('qty', $issue->qty);
                }
                $return['data'] = $issue;
                $statusCode = 200;
                $return['status'] = 'success';
            } else {
                $return['errors']['issue_qty'] = 'Trying to Issue greater than stock qty';
            }

            return response()->json($return, $statusCode);
        } catch (\Throwable $ex) {
            return $this->error(['status' => 'error', 'main_error_message' => $ex->getMessage()]);
        }
    }

    public function issue_undo(Request $request) {
        try {
            $statusCode = 422;
            $return = [];

            $id = $request->input('id');
            $issue = SubStoreIssue::find($id);

            if ($issue) {
                $created_at = $issue->created_at;
                $now = \Carbon\Carbon::now();
                $diffInHours = $now->diffInHours($created_at);

                if ($diffInHours <= 72) {
                    $substore = SubStore::find($issue->substore_id);
                    if ($substore) {
                        $substore->increment('qty', $issue->qty);
                    }

                    if ($issue->delete()) {
                        $statusCode = 200;
                        $return['status'] = 'success';
                    } else {
                        $return['errors']['message'] = 'Failed to delete issue record.';
                    }
                } else {
                    $return['errors']['message'] = 'Undo operation is allowed only within 72 hours of record creation.';
                }
            } else {
                $return['errors']['message'] = 'Issue record not found.';
            }

            return response()->json($return, $statusCode);
        } catch (\Throwable $ex) {
            return $this->error(['status' => 'error', 'main_error_message' => $ex->getMessage()]);
        }
    }

//    RECEIVE REPORT AREA 

    public function receive_report(Request $request) {
        try {
            $statusCode = 422;
            $return = [];

            $validator = Validator::make($request->all(), [
                'period' => 'required',
                'year' => 'required_if:period,Monthly,Yearly|nullable',
                'month' => 'required_if:period,Monthly|nullable',
                'from_date' => 'required_if:period,Custom|nullable',
                'to_date' => 'required_if:period,Custom|nullable',
                'type' => 'nullable',
                'supplier_id' => 'nullable',
                'item_id' => 'nullable',
            ]);

            if ($validator->fails()) {
                return response()->json(['errors' => $validator->errors()], 422);
            }

            // inputs from frontend
            $period = $request->input('period');
            $year = $request->input('year');
            $month = $request->input('month');
            $from_date = $request->input('from_date');
            $to_date = $request->input('to_date');
            $type = $request->input('type');
            $supplier_id = $request->input('supplier_id');
            $item_id = $request->input('item_id');
            $user = \App\Models\User::find($request->user->id);

            $reportSummary = [];

            //QUERY
            $company_id = $request->input('company_id') ? $request->input('company_id') : $user->company;
            $company = \App\Models\Company::find($company_id);
            $query = SubStoreReceive::where('company_id', $company_id);

            if ($period == "Monthly") {
                $query->whereYear('receive_date', $year)
                        ->whereMonth('receive_date', $month);
                $monthName = Carbon::createFromDate(null, $month, 1)->monthName;
                $reportSummary['report_type'] = "Monthly";
                $reportSummary['report_month'] = $monthName;
                $reportSummary['report_year'] = $year;
            } else if ($period == "Yearly") {
                $query->whereYear('receive_date', $year);
                $reportSummary['report_type'] = "Yearly";
                $reportSummary['report_year'] = $year;
            } else if ($period == "Custom") {

                $from_date = date('Y-m-d', strtotime($from_date));
                $to_date = date('Y-m-d', strtotime($to_date . ' +1 day')); // Include end of day
                $query->whereBetween('receive_date', [$from_date, $to_date]);

                $fromDate = Carbon::createFromFormat('Y-m-d', $from_date)->format('jS M, Y'); // Format from_date
                $toDate = Carbon::createFromFormat('Y-m-d', $to_date)->format('jS M, Y'); // Format to_date
                $reportSummary['report_type'] = "Custom";
                $reportSummary['report_from_date'] = $fromDate;
                $reportSummary['report_to_date'] = $toDate;
            }


            $reportSummary['part_type'] = $type ?? '';
            $reportSummary['company_name'] = $company->title;
            $supplier = \App\Models\Supplier::where('id', $supplier_id)->first();
            $reportSummary['supplier'] = $supplier ? $supplier->company_name : "";

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

            $receives = $query->get();

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
            $return['data'] = $receives;
            $return['reportSummary'] = $reportSummary;
            $return['status'] = "success";
            $statusCode = 200;
            return $this->response($return, $statusCode);
        } catch (\Throwable $ex) {
            return $this->error(['status' => 'error', 'main_error_message' => $ex->getMessage()]);
        }
    }

    //END RECEIVE REPORT AREA
    //START ISSUE REPORT
    public function issue_report(Request $request) {
        try {
            $statusCode = 422;
            $return = [];

            $validator = Validator::make($request->all(), [
                'period' => 'required',
                'year' => 'required_if:period,Monthly,Yearly|nullable',
                'month' => 'required_if:period,Monthly|nullable',
                'from_date' => 'required_if:period,Custom|nullable',
                'to_date' => 'required_if:period,Custom|nullable',
                'type' => 'nullable',
                'employee_id' => 'nullable',
                'item_id' => 'nullable',
                'issue_type' => 'nullable',
            ]);

            if ($validator->fails()) {
                return response()->json(['errors' => $validator->errors()], 422);
            }

            // inputs from frontend
            $period = $request->input('period');
            $year = $request->input('year');
            $month = $request->input('month');
            $from_date = $request->input('from_date');
            $to_date = $request->input('to_date');
            $type = $request->input('type');
            $employee_id = $request->input('employee_id');
            $issue_type = $request->input('issue_type');
            $item_id = $request->input('item_id');
            $user = \App\Models\User::find($request->user->id);

            $reportSummary = [];

            //QUERY

            $company_id = $request->input('company_id') ? $request->input('company_id') : $user->company;
            $company = \App\Models\Company::find($company_id);
            $query = SubStoreIssue::where('company_id', $company_id);

            if ($period == "Monthly") {
                $query->whereYear('issue_date', $year)
                        ->whereMonth('issue_date', $month);
                $monthName = Carbon::createFromDate(null, $month, 1)->monthName;
                $reportSummary['report_type'] = "Monthly";
                $reportSummary['report_month'] = $monthName;
                $reportSummary['report_year'] = $year;
            } else if ($period == "Yearly") {
                $query->whereYear('issue_date', $year);
                $reportSummary['report_type'] = "Yearly";
                $reportSummary['report_year'] = $year;
            } else if ($period == "Custom") {
                $from_date = date('Y-m-d', strtotime($from_date));
                $to_date = date('Y-m-d', strtotime($to_date . ' +1 day')); // Include end of day
                $query->whereBetween('issue_date', [$from_date, $to_date]);

                $fromDate = Carbon::createFromFormat('Y-m-d', $from_date)->format('jS M, Y'); // Format from_date
                $toDate = Carbon::createFromFormat('Y-m-d', $to_date)->format('jS M, Y'); // Format to_date
                $reportSummary['report_type'] = "Custom";
                $reportSummary['report_from_date'] = $fromDate;
                $reportSummary['report_to_date'] = $toDate;
            }


            $reportSummary['part_type'] = $type ?? '';
            $reportSummary['company_name'] = $company->title;
            $employee = \App\Models\User::where('id', $employee_id)->first();
            $reportSummary['employee'] = $employee ? $employee->full_name : "";

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

            $issues = $query->orderBy('issue_date', 'desc')->get();

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

                $part_request = PartRequest::where('id', $val->request_id)->first();
                $val->request_number = $part_request ? $part_request->request_number : 'N/A';
                $part = \App\Models\Part::where('id', $val->part_id)->first();
                $val->part_name = $part->title;
                $val->unit = $part->unit;
                $val->type = $part->type;
            }
            $return['data'] = $issues;
            $return['reportSummary'] = $reportSummary;
            $return['status'] = "success";
            $statusCode = 200;
            return $this->response($return, $statusCode);
        } catch (\Throwable $ex) {
            return $this->error(['status' => 'error', 'main_error_message' => $ex->getMessage()]);
        }
    }

    //END ISSUE REPORT
    //Part Request Functions
    public function part_requests(Request $request) {
        try {
            $statusCode = 422;
            $return = [];
            $department_title = $request->input('department');
            $designation_title = $request->input('designation');
            $user = \App\Models\User::find($request->user->id);
            $query = PartRequest::orderBy('created_at', 'desc');
            $own_request = PartRequest::where('user_id', $user->id);

            if ($department_title == 'Store' && $designation_title == 'Store Keeper') {
                $query->where('company_id', $user->company)
                        ->whereNotIn('status', ['Pending', 'Rejected', 'Revised', 'Cancelled']);
            } else if ($department_title == 'Administration' && $designation_title == 'Store Keeper') {
                $query->where('company_id', $user->company)
                        ->whereNotIn('status', ['Pending', 'Rejected', 'Revised', 'Cancelled']);
            } else if ($department_title == 'Administration' && $designation_title == 'Receptionist') {
                $query->where('company_id', $user->company)
                        ->whereNotIn('status', ['Pending', 'Rejected', 'Revised', 'Cancelled']);
            } else if ($department_title == "Merchandising" && $designation_title == "Merchandiser") {
                $query->where('user_id', $user->id);
            } else if ($department_title == "Merchandising" && $designation_title == "Sr. Merchandiser") {
                $query->where('user_id', $user->id);
            } else if ($department_title == "Audit" && $designation_title == "Manager") {
                $query->where('company_id', $user->company)
                        ->whereNotIn('status', ['Pending', 'Rejected', 'Revised', 'Cancelled']);
            } else if ($department_title == "Merchandising" && $designation_title == "Asst. Merchandiser") {
                $query->where('user_id', $user->id);
            } else if ($department_title == "Merchandising" && $designation_title == "Assistant Manager") {
                $find_user_team = Team::whereRaw("FIND_IN_SET('$user->id', employees)")->first();
                $team_users = explode(',', $find_user_team->employees);
                $query->whereIn('user_id', $team_users)->where('department', $user->department)
                        ->where('company_id', $user->company)
                        ->where('status', ['Pending']);
            } else if ($designation_title == 'Manager' || $designation_title === "General Manager") {
                $query->where('department', $user->department)
                        ->where('company_id', $user->company)
                        ->where('status', ['Pending']);
            } else {
                $query->where('user_id', $user->id);
            }
            $query->union($own_request);
            $requests = $query->orderBy('created_at', 'desc')->paginate(100);

            if ($requests->isNotEmpty()) {
                foreach ($requests as $val) {
                    $user = \App\Models\User::find($val->user_id);
                    $val->requisition_by = $user->full_name;
                    $company = \App\Models\Company::find($val->company_id);
                    $val->company = $company->title;
                    $part = \App\Models\Part::where('id', $val->part_id)->first();
                    $val->part_name = $part->title;
                    $val->unit = $part->unit;
                    $val->image_source = url('') . '/parts/' . $part->photo;
                    $substore = SubStore::find($val->substore_id);
                    $val->stock_qty = $substore ? $substore->qty : null;

                    switch ($val->status) {
                        case 'Approved':
                            $action_user_id = $val->approved_by;
                            break;
                        case 'Rejected':
                            $action_user_id = $val->rejected_by;
                            break;
                        case 'Delivered':
                            $action_user_id = $val->delivered_by;
                            break;
                        case 'Cancelled':
                            $action_user_id = $val->cancelled_by;
                            break;
                        default:
                            $action_user_id = null;
                    }

                    // Set action user name
                    if ($action_user_id) {
                        $action_user = \App\Models\User::find($action_user_id);
                        $val->action_user = $action_user ? $action_user->full_name : "N/A";
                    } else {
                        $val->action_user = "N/A";
                    }
                }
                $return['requests'] = $requests;
                $statusCode = 200;
            } else {
                $return['status'] = 'error';
            }
            return $this->response($return, $statusCode);
        } catch (\Throwable $ex) {
            return $this->error(['status' => 'error', 'main_error_message' => $ex->getMessage(), 'AT LINE' => $ex->getLine()]);
        }
    }

    public function part_requests_create(Request $request) {
        try {
            $statusCode = 200;
            $return = [];
            $request_items = json_decode($request->input('request_items'));
            if (empty($request_items)) {
                return response()->json(['errors' => ['request_items' => 'No request items found']], 400);
            }
            $line = $request->input('line');

            $created_requests = [];

            foreach ($request_items as $val) {
                $substore = SubStore::find($val->substore_id);
                if (!$substore) {
                    return response()->json(['errors' => ['id' => 'Substore not found']], 422);
                }
                if ($val->qty > $substore->qty) {
                    return response()->json(['errors' => ['qty' => 'Requested quantity exceeds available stock']], 422);
                }
                $user = \App\Models\User::find($request->user->id);
                $part_request = new PartRequest();
                $part_request->user_id = $user->id;
                $part_request->substore_id = $substore->id;
                $part_request->part_id = $substore->part_id;
                $part_request->qty = $val->qty;
                $part_request->company_id = $substore->company_id;
                $part_request->remarks = $val->remarks;
                $part_request->line = $line;
                $part_request->department = $user->department;
                $part = \App\Models\Part::find($substore->part_id);

                if ($part && $part->type === "Stationery") {
                    $part_request->status = 'Approved';
                    $notify_users = \App\Models\User::where('company', $part_request->company_id)->whereIn('designation', [30, 32])->get();
                    foreach ($notify_users as $notify_user) {
                        $notification = new \App\Models\Notification;
                        $notification->title = "Substore Requisition From " . $user->full_name;
                        $notification->receiver = $notify_user->id;
                        $notification->url = "/part-requests";
                        $notification->description = $part_request->qty . " " . $part->unit . " " . $part->title;
                        $notification->is_read = 0;
                        $notification->save();
                    }
                } else {
                    $part_request->status = 'Pending';
                }
                $part_request->save();
                $created_requests[] = $part_request;
            }
            $return['data'] = $created_requests;
            $return['status'] = 'success';

            return response()->json($return, $statusCode);
        } catch (\Throwable $ex) {
            return response()->json(['status' => 'error', 'main_error_message' => $ex->getMessage()], 500);
        }
    }

    public function part_requests_show(Request $request) {
        try {
            $id = $request->input('id');
            $part_request = PartRequest::find($id);
            if ($part_request) {
                $user = \App\Models\User::find($part_request->user_id);
                $part = \App\Models\Part::find($part_request->part_id);
                $substore = SubStore::find($part_request->substore_id);

                $part_request->user = $user ? $user->full_name : null;
                $part_request->part_name = $part ? $part->title : null;
                $part_request->stock_qty = $substore ? $substore->qty : null;

                $return['data'] = $part_request;
                $return['status'] = 'success';
                return response()->json($return, 200);
            } else {
                return response()->json(['status' => 'error', 'main_error_message' => 'Part Request not found'], 404);
            }
        } catch (\Throwable $ex) {
            return response()->json(['status' => 'error', 'main_error_message' => $ex->getMessage()], 500);
        }
    }

    public function part_requests_update(Request $request) {
        try {
            $id = $request->input('id');
            $part_request = PartRequest::find($id);
            if (!$part_request) {
                return response()->json(['errors' => ['id' => 'Part request not found']], 404);
            }

            $substore = SubStore::find($request->input('substore_id'));
            if (!$substore) {
                return response()->json(['errors' => ['substore_id' => 'Substore not found']], 422);
            }

            if ($request->input('qty') > $substore->qty) {
                return response()->json(['errors' => ['qty' => 'Requested quantity exceeds available stock']], 422);
            }
            $part_request->substore_id = $substore->id;
            $part_request->part_id = $substore->part_id;
            $part_request->qty = $request->qty;
            $part_request->remarks = $request->remarks;

            $part = \App\Models\Part::find($substore->part_id);
            if ($part && $part->type === "Stationery") {
                $part_request->status = 'Approved';
            } else {
                $part_request->status = 'Pending';
            }
            $part_request->save();
            return response()->json(['data' => $part_request, 'status' => 'success'], 200);
        } catch (\Throwable $ex) {
            return response()->json(['status' => 'error', 'main_error_message' => $ex->getMessage()], 500);
        }
    }

    public function part_requests_toggle(Request $request) {
        try {
            $statusCode = 422;
            $return = [];
            // Corrected user ID retrieval


            $user = \App\Models\User::find($request->user->id);

            $id = $request->input('id');
            $status = $request->input('status');

            // Retrieve part request
            $part_request = PartRequest::find($id);

            if (!$part_request) {
                return response()->json(['errors' => ['main_error' => 'Part request not found']], 404);
            }


            $part = \App\Models\Part::where('id', $part_request->part_id)->first();

            // Check authorization here if needed
            // Start a transaction
            DB::beginTransaction();

            switch ($status) {
                case 'Cancelled':
                    $part_request->cancelled_by = $user->id;
                    $part_request->cancelled_at = now();
                    break;
                case 'Approved':
                    $part_request->approved_by = $user->id;
                    $part_request->approved_at = now();
                    $notify_users = \App\Models\User::where('company', $part_request->company_id)->whereIn('designation', [30, 32])->get();
                    foreach ($notify_users as $notify_user) {
                        $notification = new \App\Models\Notification;
                        $notification->title = "Substore Requisition From " . $user->full_name;
                        $notification->receiver = $notify_user->id;
                        $notification->url = "/part-requests";
                        $notification->description = $part_request->qty . " " . $part->unit . " " . $part->title;
                        $notification->is_read = 0;
                        $notification->save();
                    }
                    break;
                case 'Delivered':
                    $substore = SubStore::where('id', $part_request->substore_id)->first();
                    if ($substore) {
                        if ($part_request->qty <= $substore->qty) {
                            $issue = new SubStoreIssue();
                            $issue->part_id = $substore->part_id;
                            $issue->user_id = $user->id;
                            $issue->substore_id = $substore->id;
                            $issue->issue_type = 'Self';
                            $issue->issue_to = $part_request->user_id;
                            $issue->issuing_company = 0;
                            $issue->line = $part_request->line;
                            $issue->qty = $part_request->qty;
                            $issue->company_id = $part_request->company_id;
                            $issue->remarks = $part_request->remarks;
                            $issue->reference = $part_request->request_number;
                            $issue->request_id = $part_request->id;
                            $issue->issue_date = date('Y-m-d');

                            if ($issue->save()) {
                                $substore->decrement('qty', $issue->qty);
                                $part_request->delivered_by = $user->id;
                                $part_request->delivered_at = now();
                                $notification = new \App\Models\Notification;
                                $notification->title = "Item Delivered By " . $user->full_name;
                                $notification->receiver = $part_request->user_id;
                                $notification->url = "/part-requests";
                                $notification->description = $part_request->qty . " " . $part->unit . " " . $part->title;
                                $notification->is_read = 0;
                                $notification->save();
                            }
                        } else {
                            DB::rollBack();
                            return response()->json(['errors' => ['main_error' => 'Requested quantity exceeds available stock']], 422);
                        }
                    }
                    break;
                case 'Rejected':
                    $part_request->rejected_by = $user->id;
                    $part_request->rejected_at = now();
                    // Resetting other fields for consistency
                    $part_request->approved_by = 0;
                    $part_request->approved_at = null;
                    $part_request->delivered_by = 0;
                    $part_request->delivered_at = null;
                    break;
                default:
                    DB::rollBack();
                    return response()->json(['errors' => ['status' => 'Invalid status']], 400);
            }

            $part_request->status = $status;
            $part_request->save();

            // Commit transaction
            DB::commit();
            $return['data'] = $part_request;
            $return['status'] = 'success';
            $statusCode = 200;
            return $this->response($return, $statusCode);
        } catch (\Throwable $ex) {
            // Rollback transaction in case of error
            DB::rollBack();
            return response()->json(['status' => 'error', 'main_error_message' => $ex->getMessage()], 500);
        }
    }

    //MAIL SENDING
    public function mail_daily_report(Request $request) {
        // Retrieve users based on conditions
        $users = User::whereIn('email', ['faruk@fashion-product.com', 'faisal@fashion-product.com.bd', 'kayes@fashion-product.com'])
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
        $receives = SubStoreReceive::where('company_id', $companyId)
                ->whereDate('receive_date', now()->toDateString())
                ->get();

        foreach ($receives as $val) {
            $part = \App\Models\Part::find($val->part_id);
            $val->part_name = $part->title;
            $val->unit = $part->unit;
            $requsition = \App\Models\Requisition::find($val->requisition_id);
            $val->requsition_number = $requsition->requsition_number;
            $receiver = User::find($val->user_id);
            $val->received_by = $receiver->full_name;
        }

        $issues = SubStoreIssue::where('company_id', $companyId)
                ->whereDate('issue_date', now()->toDateString())
                ->get();

        foreach ($issues as $val) {
            $issue_by = \App\Models\User::where('id', $val->user_id)->first();
            $val->issue_by = $issue_by->full_name;
            if ($val->issue_type == "Self") {
                $issue_to_user = \App\Models\User::where('id', $val->issue_to)->first();
                $val->issue_to_show = $issue_to_user->full_name;
            } else if ($val->issue_type == "Sister-Factory") {
                $issue_to_company = \App\Models\Company::where('id', $val->issuing_company)->first();
                $val->issue_to_show = $issue_to_company->title;
            }
            $part = \App\Models\Part::where('id', $val->part_id)->first();
            $val->part_name = $part->title;
            $val->unit = $part->unit;
        }

        $storeSummary = SubStore::where('company_id', $companyId)->get();

        foreach ($storeSummary as $val) {
            $part = \App\Models\Part::where('id', $val->part_id)->first();
            $val->part_name = $part->title;
            $val->unit = $part->unit;
            $company = \App\Models\Company::where('id', $val->company_id)->first();
            $val->company = $company->title;
            $val->total_receives = SubStoreReceive::where('substore_id', $val->id)->sum('qty');
            $val->total_issues = SubStoreIssue::where('substore_id', $val->id)->sum('qty');
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
            Mail::to($user->email)->send(new SubstoreReportMail($reportData, $username));
        }
    }
}
