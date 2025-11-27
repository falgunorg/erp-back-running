<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\SubStore;
use App\Models\SubStoreReceive;
use App\Models\SubStoreIssue;
use App\Models\PartRequest;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\DB;
use App\Models\User;
use App\Models\RequisitionItem;
use App\Models\Requisition;
use App\Models\SubStoreAccess;
use App\Models\Part;
use Carbon\Carbon;
use App\Models\Company;
use App\Models\Supplier;

class SubStoreController extends Controller {

    public function index(Request $request) {
        try {
            $user = User::find($request->user->id);

            // Get access areas
            $access = SubStoreAccess::where('user_id', $user->id)->first();
            $accessAreas = $access ? explode(',', $access->area) : [];

            // Base query with eager loaded part
            $query = SubStore::with(['part'])
                    ->withSum('receives as total_received', 'qty')
                    ->withSum('issues as total_issued', 'qty')
                    ->where('company_id', $user->company);

            // Search filter
            if (!empty($request->search)) {
                $search = $request->search;
                $query->whereHas('part', fn($q) => $q->where('title', 'LIKE', "%{$search}%"));
            }

            // Type filter
            if ($access) {
                if ($request->type) {
                    $type = $request->type;
                    if (in_array($type, $accessAreas)) {
                        $query->whereHas('part', fn($q) => $q->where('type', $type));
                    } else {
                        $query->whereRaw('1 = 0'); // empty
                    }
                } else {
                    $query->whereHas('part', fn($q) => $q->whereIn('type', $accessAreas));
                }
            }

            // Paginate
            $substores = $query->paginate(200);

//            // Company-wise
//            $company_wise = SubStore::with('part')
//                    ->where('company_id', $user->company)
//                    ->whereHas('part', fn($q) => $q->whereIn('type', $accessAreas))
//                    ->get();
            // All data
//            $all_data = SubStore::with('part')->get();

            return $this->response([
                        'substores' => $substores,
//                        'company_wise' => $company_wise,
//                        'all_data' => $all_data,
                            ], 200);
        } catch (\Throwable $ex) {
            return $this->error([
                        'status' => 'error',
                        'main_error_message' => $ex->getMessage()
            ]);
        }
    }

    public function all_data(Request $request) {
        try {
            // All data
            $all_data = SubStore::with('part')->get();
            return $this->response([
                        'all_data' => $all_data,
                            ], 200);
        } catch (\Throwable $ex) {
            return $this->error([
                        'status' => 'error',
                        'main_error_message' => $ex->getMessage()
            ]);
        }
    }

    public function company_wise(Request $request) {
        try {
            $user = User::find($request->user->id);

            // Get access areas
            $access = SubStoreAccess::where('user_id', $user->id)->first();
            $accessAreas = $access ? explode(',', $access->area) : [];

            // Company-wise
            $company_wise = SubStore::with('part')
                    ->where('company_id', $user->company)
                    ->whereHas('part', fn($q) => $q->whereIn('type', $accessAreas))
                    ->get();

            return $this->response([
                        'company_wise' => $company_wise,
                            ], 200);
        } catch (\Throwable $ex) {
            return $this->error([
                        'status' => 'error',
                        'main_error_message' => $ex->getMessage()
            ]);
        }
    }

    public function show(Request $request) {
        try {
            $id = $request->input('id');

            // Load all necessary relations in one query
            $substore = SubStore::with([
                        'part',
                        'receives' => fn($q) => $q->orderBy('receive_date', 'desc'),
                        'receives.requisition',
                        'receives.user',
                        'receives.supplier',
                        'issues' => fn($q) => $q->orderBy('issue_date', 'desc'),
                        'issues.user',
                        'issues.issueToUser',
                        'issues.issueToCompany'
                    ])
                    ->withSum('receives as total_received', 'qty')
                    ->withSum('issues as total_issued', 'qty')
                    ->find($id);

            if (!$substore) {
                return $this->response(['status' => 'error', 'message' => 'Not found'], 422);
            }

            return $this->response([
                        'status' => 'success',
                        'data' => $substore
                            ], 200);
        } catch (\Throwable $ex) {
            return $this->error([
                        'status' => 'error',
                        'main_error_message' => $ex->getMessage()
            ]);
        }
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
            $user = User::find($request->user->id);

            $reportSummary = [];

            //QUERY
            $company_id = $request->input('company_id') ? $request->input('company_id') : $user->company;
            $company = Company::find($company_id);
            $query = SubStoreReceive::with('user', 'substore.part', 'supplier', 'requisition', 'substore.company', 'requisitionItem')->where('company_id', $company_id);

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
            $supplier = Supplier::where('id', $supplier_id)->first();
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
            $return['data'] = $receives;
            $return['reportSummary'] = $reportSummary;
            $return['status'] = "success";
            $statusCode = 200;
            return $this->response($return, $statusCode);
        } catch (\Throwable $ex) {
            return $this->error(['status' => 'error', 'main_error_message' => $ex->getMessage()]);
        }
    }

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
            $company = Company::find($company_id);
            $query = SubStoreIssue::with('substore.part',
                            'user', 'issueToUser',
                            'issueToCompany')
                    ->where('company_id', $company_id);

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
            $employee = User::where('id', $employee_id)->first();
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

            $return['data'] = $issues;
            $return['reportSummary'] = $reportSummary;
            $return['status'] = "success";
            $statusCode = 200;
            return $this->response($return, $statusCode);
        } catch (\Throwable $ex) {
            return $this->error(['status' => 'error', 'main_error_message' => $ex->getMessage()]);
        }
    }

    public function make_report(Request $request) {
        try {
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

            $period = $request->period;
            $year = $request->year;
            $month = $request->month;
            $from_date = $request->from_date;
            $to_date = $request->to_date;
            $type = $request->type;
            $item_id = $request->item_id;

            $user = User::find($request->user->id);
            $company_id = $request->company_id ?? $user->company;
            $company = Company::find($company_id);

            // --------------------------------------------
            // 🔥 Load Substore + all receives/issues ONCE
            // --------------------------------------------
            $substores = SubStore::with([
                        'part',
                        'receives:id,substore_id,part_id,qty,receive_date',
                        'issues:id,substore_id,part_id,qty,issue_date'
                    ])
                    ->where('company_id', $company_id)
                    ->when($item_id, fn($q) => $q->where('id', $item_id))
                    ->when($type, fn($q) =>
                            $q->whereHas('part', fn($p) => $p->where('type', $type))
                    )
                    ->get();

            // --------------------------------------------
            // Generate Report Based on Type
            // --------------------------------------------
            if ($period === "Monthly") {
                $report = $this->monthly($substores, $year, $month);
                $reportSummary = [
                    'report_type' => "Monthly",
                    'report_month' => Carbon::createFromDate(null, $month, 1)->monthName,
                    'report_year' => $year,
                    'report_length' => Carbon::createFromDate($year, $month, 1)->daysInMonth
                ];
            } elseif ($period === "Yearly") {
                $report = $this->yearly($substores, $year);
                $reportSummary = [
                    'report_type' => "Yearly",
                    'report_year' => $year,
                    'report_length' => 12
                ];
            } else { // Custom
                $report = $this->custom($substores, $from_date, $to_date);
                $reportSummary = [
                    'report_type' => "Custom",
                    'report_from_date' => Carbon::parse($from_date)->format("jS M, Y"),
                    'report_to_date' => Carbon::parse($to_date)->format("jS M, Y"),
                ];
            }

            // Add extra metadata
            $reportSummary['part_type'] = $type ?? 'All';
            $reportSummary['company_name'] = $company->title;

            return $this->response([
                        'data' => $report,
                        'reportSummary' => $reportSummary
                            ], 200);
        } catch (\Throwable $ex) {
            return $this->error([
                        'status' => 'error',
                        'main_error_message' => $ex->getMessage()
            ]);
        }
    }

    private function monthly($substores, $year, $month) {
        $report = [];
        $start = Carbon::createFromDate($year, $month, 1)->startOfMonth();
        $end = $start->copy()->endOfMonth();

        foreach ($substores as $s) {


            $receives_month = $s->receives
                    ->whereBetween('receive_date', [$start, $end])
                    ->sum('qty');

            $issues_month = $s->issues
                    ->whereBetween('issue_date', [$start, $end])
                    ->sum('qty');

            // -------- Daily logs (NO DB hit) --------
            $records = [];
            foreach (range(1, $start->daysInMonth) as $d) {
                $date = sprintf('%04d-%02d-%02d', $year, $month, $d);
                $records[] = [
                    'date' => $date,
                    'receives' => $s->receives->where('receive_date', $date)->sum('qty'),
                    'issues' => $s->issues->where('issue_date', $date)->sum('qty'),
                ];
            }

            $part = $s->part;

            $report[] = [
                'item_title' => $part->title,
                'unit' => $part->unit,
                'type' => $part->type,
                'total_receives' => $receives_month,
                'total_issues' => $issues_month,
                'records' => $records
            ];
        }

        return $report;
    }

    private function yearly($substores, $year) {
        $report = [];

        foreach ($substores as $s) {
            // Yearly Summary
            $receives_year = $s->receives()
                    ->whereYear('receive_date', $year)
                    ->sum('qty');

            $issues_year = $s->issues()
                    ->whereYear('issue_date', $year)
                    ->sum('qty');

            // Monthly breakdown
            $records = [];

            for ($m = 1; $m <= 12; $m++) {
                $start = Carbon::createFromDate($year, $m, 1);
                $end = $start->copy()->endOfMonth();

                $monthReceives = $s->receives()
                        ->whereBetween('receive_date', [$start, $end])
                        ->sum('qty');

                $monthIssues = $s->issues()
                        ->whereBetween('issue_date', [$start, $end])
                        ->sum('qty');

                $records[] = [
                    'month' => $m,
                    'receives' => $monthReceives,
                    'issues' => $monthIssues,
                ];
            }

            $part = $s->part;

            $report[] = [
                'item_title' => $part->title,
                'unit' => $part->unit,
                'type' => $part->type,
                'total_receives' => $receives_year,
                'total_issues' => $issues_year,
                'records' => $records
            ];
        }

        return $report;
    }

    private function custom($substores, $from_date, $to_date) {
        $report = [];
        $start = Carbon::parse($from_date)->startOfDay();
        $end = Carbon::parse($to_date)->endOfDay();

        foreach ($substores as $s) {



            $receives_range = $s->receives
                    ->whereBetween('receive_date', [$start, $end])
                    ->sum('qty');

            $issues_range = $s->issues
                    ->whereBetween('issue_date', [$start, $end])
                    ->sum('qty');

            $part = $s->part;

            $report[] = [
                'item_title' => $part->title,
                'unit' => $part->unit,
                'type' => $part->type,
                'total_receives' => $receives_range,
                'total_issues' => $issues_range,
                'records' => []
            ];
        }

        return $report;
    }

//    //START SUBSTORE REPORT
//    public function make_report(Request $request) {
//        try {
//            $statusCode = 422;
//            $return = [];
//
//            $validator = Validator::make($request->all(), [
//                'period' => 'required',
//                'year' => 'required_if:period,Monthly,Yearly|nullable',
//                'month' => 'required_if:period,Monthly|nullable',
//                'from_date' => 'required_if:period,Custom|nullable',
//                'to_date' => 'required_if:period,Custom|nullable',
//                'type' => 'nullable',
//                'item_id' => 'nullable',
//            ]);
//
//            if ($validator->fails()) {
//                return response()->json(['errors' => $validator->errors()], 422);
//            }
//
//            // inputs from frontend
//            $period = $request->input('period');
//            $year = $request->input('year');
//            $month = $request->input('month');
//            $from_date = $request->input('from_date');
//            $to_date = $request->input('to_date');
//            $type = $request->input('type');
//            $item_id = $request->input('item_id');
//
//            $user = User::find($request->user->id);
//            $company_id = $request->input('company_id') ? $request->input('company_id') : $user->company;
//            $company = Company::find($company_id);
//            $query = SubStore::with('part',
//                            'company',
//                            'receives',
//                            'issues')
//                    ->where('company_id', $company_id);
//
//            if ($item_id) {
//                $query->where('id', $item_id);
//            }
//
//            if ($type) {
//                $query->whereHas('part', function ($q) use ($type) {
//                    $q->where('type', $type);
//                });
//            }
//
//            $substores = $query->get();
//            $report = [];
//
//            $reportSummary = [];
//
//            if ($period == "Monthly") {
//                $report = $this->generateMonthlyReport($substores, $year, $month);
//                $monthName = Carbon::createFromDate(null, $month, 1)->monthName; // Get month name
//                $daysInMonth = Carbon::createFromDate($year, $month, 1)->daysInMonth;
//                $reportSummary['report_type'] = "Monthly";
//                $reportSummary['report_month'] = $monthName;
//                $reportSummary['report_length'] = $daysInMonth;
//                $reportSummary['report_year'] = $year;
//            } else if ($period == "Yearly") {
//                $report = $this->generateYearlyReport($substores, $year);
//                $reportSummary['report_type'] = "Yearly";
//                $reportSummary['report_year'] = $year;
//                $reportSummary['report_length'] = 12;
//            } else if ($period == "Custom") {
//                $report = $this->generateCustomReport($substores, $from_date, $to_date);
//                $fromDate = Carbon::createFromFormat('Y-m-d', $from_date)->format('jS M, Y'); // Format from_date
//                $toDate = Carbon::createFromFormat('Y-m-d', $to_date)->format('jS M, Y'); // Format to_date
//                $reportSummary['report_type'] = "Custom";
//                $reportSummary['report_from_date'] = $fromDate;
//                $reportSummary['report_to_date'] = $toDate;
//            }
//            $reportSummary['part_type'] = $type ?? 'All';
//            $reportSummary['company_name'] = $company->title;
//
//            $return['data'] = $report;
//            $return['reportSummary'] = $reportSummary; // Add reportSummary to response
//            $statusCode = 200;
//            return $this->response($return, $statusCode);
//        } catch (\Throwable $ex) {
//            return $this->error(['status' => 'error', 'main_error_message' => $ex->getMessage()]);
//        }
//    }
//
//    private function generateMonthlyReport($substores, $year, $month) {
//        $report = [];
//
//        // Create a Carbon instance for the selected month
//        $selected_month = Carbon::createFromDate($year, $month, 1)->startOfMonth();
//
//        foreach ($substores as $substore) {
//
//
//            $total_receives = SubStoreReceive::where('substore_id', $substore->id)
//                            ->where('part_id', $substore->part_id)->sum('qty');
//
//            $total_issues = SubStoreIssue::where('substore_id', $substore->id)
//                            ->where('part_id', $substore->part_id)->sum('qty');
//
//            $transaction_balance = $total_receives - $total_issues;
//            $startingBalance = $substore->qty - $transaction_balance;
//
//            // Compute opening balance before the selected month
//            $totalReceivesBeforeFrom = SubStoreReceive::where('substore_id', $substore->id)
//                    ->where('part_id', $substore->part_id)
//                    ->where('receive_date', '<', $selected_month)
//                    ->sum('qty');
//
//            $totalIssuesBeforeFrom = SubStoreIssue::where('substore_id', $substore->id)
//                    ->where('part_id', $substore->part_id)
//                    ->where('issue_date', '<', $selected_month)
//                    ->sum('qty');
//            $openingBalance = $totalReceivesBeforeFrom - $totalIssuesBeforeFrom;
//
//            // Compute total receives and issues for the selected month
//            $total_receives_this_month = SubStoreReceive::where('substore_id', $substore->id)
//                    ->where('part_id', $substore->part_id)
//                    ->whereYear('receive_date', $year)
//                    ->whereMonth('receive_date', $month)
//                    ->sum('qty');
//
//            $total_issues_this_month = SubStoreIssue::where('substore_id', $substore->id)
//                    ->where('part_id', $substore->part_id)
//                    ->whereYear('issue_date', $year)
//                    ->whereMonth('issue_date', $month)
//                    ->sum('qty');
//
//            $daysInMonth = $selected_month->daysInMonth;
//            $records = [];
//
//            for ($day = 1; $day <= $daysInMonth; $day++) {
//                $date = sprintf('%04d-%02d-%02d', $year, $month, $day);
//
//                $daily_receive = SubStoreReceive::where('substore_id', $substore->id)
//                        ->where('part_id', $substore->part_id)
//                        ->whereDate('receive_date', $date)
//                        ->sum('qty');
//
//                $daily_issue = SubStoreIssue::where('substore_id', $substore->id)
//                        ->where('part_id', $substore->part_id)
//                        ->whereDate('issue_date', $date)
//                        ->sum('qty');
//
//                $records[] = [
//                    'date' => $date,
//                    'receives' => $daily_receive,
//                    'issues' => $daily_issue,
//                ];
//            }
//
//            $part = \App\Models\Part::where('id', $substore->part_id)->first();
//            $report[] = [
//                'item_title' => $part->title,
//                'unit' => $part->unit,
//                'type' => $part->type,
//                'opening_balance' => $openingBalance + $startingBalance,
//                'total_receives' => $total_receives_this_month,
//                'total_issues' => $total_issues_this_month,
//                'total' => $openingBalance + $startingBalance + $total_receives_this_month,
//                'balance' => ($openingBalance + $startingBalance + $total_receives_this_month) - $total_issues_this_month,
//                'records' => $records,
//            ];
//        }
//
//        return $report;
//    }
//
//    private function generateYearlyReport($substores, $year) {
//        $report = [];
//
//        foreach ($substores as $substore) {
//            // Initialize totals and opening balance
//            $total_receives = SubStoreReceive::where('substore_id', $substore->id)
//                            ->where('part_id', $substore->part_id)->sum('qty');
//
//            $total_issues = SubStoreIssue::where('substore_id', $substore->id)
//                            ->where('part_id', $substore->part_id)->sum('qty');
//
//            $transaction_balance = $total_receives - $total_issues;
//            $startingBalance = $substore->qty - $transaction_balance;
//
//            // Compute opening balance before the selected year
//            $totalReceivesBeforeFrom = SubStoreReceive::where('substore_id', $substore->id)
//                    ->where('part_id', $substore->part_id)
//                    ->where('receive_date', '<', Carbon::createFromDate($year, 1, 1))
//                    ->sum('qty');
//
//            $totalIssuesBeforeFrom = SubStoreIssue::where('substore_id', $substore->id)
//                    ->where('part_id', $substore->part_id)
//                    ->where('issue_date', '<', Carbon::createFromDate($year, 1, 1))
//                    ->sum('qty');
//
//            $openingBalance = $totalReceivesBeforeFrom - $totalIssuesBeforeFrom;
//
//            $total_receives_this_year = SubStoreReceive::where('substore_id', $substore->id)
//                    ->where('part_id', $substore->part_id)
//                    ->whereYear('receive_date', $year)
//                    ->sum('qty');
//
//            $total_issues_this_year = SubStoreIssue::where('substore_id', $substore->id)
//                    ->where('part_id', $substore->part_id)
//                    ->whereYear('issue_date', $year)
//                    ->sum('qty');
//
//            $records = [];
//
//            for ($month = 1; $month <= 12; $month++) {
//                $startDate = Carbon::createFromDate($year, $month, 1)->startOfMonth();
//                $endDate = Carbon::createFromDate($year, $month, 1)->endOfMonth();
//
//                $totalReceivesThisMonth = SubStoreReceive::where('substore_id', $substore->id)
//                        ->where('part_id', $substore->part_id)
//                        ->whereBetween('receive_date', [$startDate, $endDate])
//                        ->sum('qty');
//
//                $totalIssuesThisMonth = SubStoreIssue::where('substore_id', $substore->id)
//                        ->where('part_id', $substore->part_id)
//                        ->whereBetween('issue_date', [$startDate, $endDate])
//                        ->sum('qty');
//
//                $records[] = [
//                    'month' => $month,
//                    'receives' => $totalReceivesThisMonth,
//                    'issues' => $totalIssuesThisMonth,
//                ];
//            }
//
//            $part = \App\Models\Part::where('id', $substore->part_id)->first();
//            $report[] = [
//                'item_title' => $part->title,
//                'unit' => $part->unit,
//                'type' => $part->type,
//                'opening_balance' => $openingBalance + $startingBalance,
//                'total_receives' => $total_receives_this_year,
//                'total_issues' => $total_issues_this_year,
//                'total' => $openingBalance + $startingBalance + $total_receives_this_year,
//                'balance' => ($openingBalance + $startingBalance + $total_receives_this_year) - $total_issues_this_year,
//                'records' => $records,
//            ];
//        }
//
//        return $report;
//    }
//
//    private function generateCustomReport($substores, $from_date, $to_date) {
//        $report = [];
//
//        foreach ($substores as $substore) {
//            // Compute transaction balance and starting balance
//            $total_receives = SubStoreReceive::where('substore_id', $substore->id)
//                            ->where('part_id', $substore->part_id)->sum('qty');
//
//            $total_issues = SubStoreIssue::where('substore_id', $substore->id)
//                            ->where('part_id', $substore->part_id)->sum('qty');
//
//            $transaction_balance = $total_receives - $total_issues;
//            $startingBalance = $substore->qty - $transaction_balance;
//
//            $from_date = date('Y-m-d', strtotime($from_date));
//            $to_date = date('Y-m-d', strtotime($to_date . ' +1 day')); // Include end of day
//            // Compute opening balance before the selected date range
//            $totalReceivesBeforeFrom = SubStoreReceive::where('substore_id', $substore->id)
//                    ->where('part_id', $substore->part_id)
//                    ->where('receive_date', '<', $from_date)
//                    ->sum('qty');
//
//            $totalIssuesBeforeFrom = SubStoreIssue::where('substore_id', $substore->id)
//                    ->where('part_id', $substore->part_id)
//                    ->where('issue_date', '<', $from_date)
//                    ->sum('qty');
//            $openingBalance = $totalReceivesBeforeFrom - $totalIssuesBeforeFrom;
//
//            // Compute total receives and issues within the selected date range
//            $totalReceivesInRange = SubStoreReceive::where('substore_id', $substore->id)
//                    ->where('part_id', $substore->part_id)
//                    ->whereBetween('receive_date', [$from_date, $to_date])
//                    ->sum('qty');
//
//            $totalIssuesInRange = SubStoreIssue::where('substore_id', $substore->id)
//                    ->where('part_id', $substore->part_id)
//                    ->whereBetween('issue_date', [$from_date, $to_date])
//                    ->sum('qty');
//
//            $part = \App\Models\Part::where('id', $substore->part_id)->first();
//            $report[] = [
//                'item_title' => $part->title,
//                'unit' => $part->unit,
//                'type' => $part->type,
//                'opening_balance' => $openingBalance + $startingBalance,
//                'total_receives' => $totalReceivesInRange,
//                'total_issues' => $totalIssuesInRange,
//                'total' => $openingBalance + $startingBalance + $totalReceivesInRange,
//                'balance' => ($openingBalance + $startingBalance + $totalReceivesInRange) - $totalIssuesInRange,
//                'records' => [],
//            ];
//        }
//
//        return $report;
//    }
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
}
