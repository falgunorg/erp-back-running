<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Budget;
use Illuminate\Support\Facades\Validator;
use App\Models\BudgetItem;
use App\Models\Team;
use App\Models\Costing;

class BudgetController extends Controller {

    public function index(Request $request) {
        try {
            $from_date = $request->input('from_date');
            $to_date = $request->input('to_date');
            $status = $request->input('status');
            $techpack_id = $request->input('techpack_id');
            $filter_items = $request->input('filter_items');

            $query = Budget::with([
                        'items',
                        'user',
                        'costing.items',
                        'techpack.buyer',
                        'techpack.company',
                        'techpack.materials'
                    ])->orderBy('created_at', 'desc');

            // Apply filters
            if (!empty($status)) {
                $query->where('status', $status);
            }

            if (!empty($techpack_id)) {
                $query->where('technical_package_id', $techpack_id);
            }

            if (!empty($filter_items) && is_array($filter_items)) {
                $query->whereIn('id', $filter_items);
            }

            if (!empty($from_date) && !empty($to_date)) {
                $query->whereBetween('created_at', [$from_date, $to_date]);
            }

            $budgets = $query->paginate(30);

            return $this->response([
                        'budgets' => $budgets
                            ], 200);
        } catch (\Throwable $ex) {
            return $this->error([
                        'status' => 'error',
                        'main_error_message' => $ex->getMessage()
            ]);
        }
    }

    public function public_index(Request $request) {
        $budgets = Budget::with([
                    'items',
                    'costing.items',
                    'user',
                    'techpack.buyer',
                    'techpack.company',
                    'techpack.materials'
                ])->orderBy('created_at', 'desc')->get();

        return $this->response([
                    'budgets' => $budgets
                        ], 200);
    }

    public function store(Request $request) {
        try {
            $statusCode = 422;
            $budgetItems = json_decode($request->input('budget_items'));
            $validator = Validator::make($request->all(), [
                'costing_id' => 'required|unique:budgets,costing_id',
                'po_id' => 'nullable',
                'wo_id' => 'nullable',
                'factory_cpm' => 'nullable|numeric',
                'fob' => 'nullable|numeric',
                'cm' => 'nullable|numeric',
            ]);

            if ($validator->fails()) {
                return response()->json([
                            'errors' => $validator->errors()
                                ], 422);
            }

            $costing = Costing::find($request->input('costing_id'));

            if (!$costing) {
                return response()->json(['error' => 'Costing not found.'], 404);
            }

            $budget = new Budget();
            $budget->user_id = $request->user->id;
            $budget->po_id = $request->input('po_id');
            $budget->wo_id = $request->input('wo_id');
            $budget->technical_package_id = $costing->technical_package_id;
            $budget->costing_id = $costing->id;
            $budget->factory_cpm = $request->input('factory_cpm');
            $budget->fob = $request->input('fob');
            $budget->cm = $request->input('cm');
            $budget->save();

            $budget->ref_number = $costing->costing_ref . '/' . $budget->id;
            $budget->save();

            if (is_array($budgetItems)) {
                foreach ($budgetItems as $val) {
                    if ($val->supplier_id == "")
                        $val->supplier_id = 0;
                    if ($val->consumption == "")
                        $val->consumption = 0;
                    if ($val->total == "")
                        $val->total = 0;
                    if ($val->unit_price == "")
                        $val->unit_price = 0;
                    if ($val->total_price == "")
                        $val->total_price = 0;
                    if ($val->actual_unit_price == "")
                        $val->actual_unit_price = 0;
                    if ($val->actual_total_price == "")
                        $val->actual_total_price = 0;
                    if ($val->quantity == "")
                        $val->quantity = 0;
                    if ($val->total_booking == "")
                        $val->total_booking = 0;

                    BudgetItem::create([
                        'budget_id' => (int) $budget->id,
                        'costing_id' => (int) $budget->costing_id,
                        'item_type_id' => (int) ($val->item_type_id ?? 0),
                        'item_id' => (int) ($val->item_id ?? 0),
                        'item_name' => $val->item_name ?? '',
                        'item_details' => $val->item_details ?? '',
                        'color' => $val->color ?? '',
                        'size' => $val->size ?? '',
                        'size_breakdown' => $val->size_breakdown ?? '',
                        'quantity' => $val->quantity ?? '',
                        'unit' => $val->unit ?? '',
                        'position' => $val->position ?? '',
                        'supplier_id' => (int) $val->supplier_id, // now it's guaranteed numeric
                        'consumption' => $val->consumption,
                        'wastage' => $val->wastage,
                        'total' => $val->total,
                        'total_booking' => $val->total_booking,
                        'quantity' => $val->quantity,
                        'unit_price' => $val->unit_price,
                        'actual_unit_price' => $val->actual_unit_price,
                        'actual_total_price' => $val->actual_total_price,
                        'total_price' => $val->total_price,
                    ]);
                }
            }

            return response()->json([
                        'status' => 'success',
                        'data' => $budget
                            ], 200);
        } catch (\Throwable $ex) {
            return response()->json([
                        'status' => 'error',
                        'message' => $ex->getMessage()
                            ], 500);
        }
    }

    public function show(Request $request) {

        $id = $request->input('id');
        $budget = Budget::with([
                    'items.supplier',
                    'items.item',
                    'items.itemType',
                    'user',
                    'techpack.buyer',
                    'techpack.company',
                ])->find($id);

        if (!$budget) {
            return response()->json(['error' => 'Budget not found.'], 404);
        }

        return response()->json([
                    'status' => 'success',
                    'data' => $budget
                        ], 200);
    }

    public function update(Request $request) {
        try {
            $statusCode = 422;
            $id = $request->input('id');
            $budgetItems = json_decode($request->input('budget_items'));

            $validator = Validator::make($request->all(), [
                'costing_id' => 'required|exists:costings,id',
                'po_id' => 'nullable',
                'wo_id' => 'nullable',
                'fob' => 'nullable|numeric',
                'cm' => 'nullable|numeric',
            ]);

            if ($validator->fails()) {
                return response()->json([
                            'errors' => $validator->errors()
                                ], 422);
            }


            $budget = Budget::find($id);
            if (!$budget) {
                return response()->json(['error' => 'Budget not found.'], 404);
            }

            $costing = Costing::find($request->input('costing_id'));

            if (!$costing) {
                return response()->json(['error' => 'Costing not found.'], 404);
            }

            // Update budget fields

            $budget->po_id = $request->input('po_id');
            $budget->wo_id = $request->input('wo_id');
            $budget->technical_package_id = $costing->technical_package_id;
            $budget->costing_id = $costing->id;
            $budget->fob = $request->input('fob');
            $budget->cm = $request->input('cm');
            $budget->ref_number = $costing->costing_ref . '/' . $budget->id;
            $budget->save();

            // Delete existing budget items
            BudgetItem::where('budget_id', $budget->id)->delete();

            // Insert updated items
            if (is_array($budgetItems)) {
                foreach ($budgetItems as $val) {
                    if ($val->supplier_id == "")
                        $val->supplier_id = 0;
                    if ($val->consumption == "")
                        $val->consumption = 0;
                    if ($val->total == "")
                        $val->total = 0;
                    if ($val->unit_price == "")
                        $val->unit_price = 0;
                    if ($val->total_price == "")
                        $val->total_price = 0;
                    if ($val->actual_unit_price == "")
                        $val->actual_unit_price = 0;
                    if ($val->actual_total_price == "")
                        $val->actual_total_price = 0;
                    if ($val->quantity == "")
                        $val->quantity = 0;
                    if ($val->total_booking == "")
                        $val->total_booking = 0;

                    BudgetItem::create([
                        'budget_id' => (int) $budget->id,
                        'costing_id' => (int) $budget->costing_id,
                        'item_type_id' => (int) ($val->item_type_id ?? 0),
                        'item_id' => (int) ($val->item_id ?? 0),
                        'item_name' => $val->item_name ?? '',
                        'item_details' => $val->item_details ?? '',
                        'color' => $val->color ?? '',
                        'size' => $val->size ?? '',
                        'size_breakdown' => $val->size_breakdown ?? '',
                        'quantity' => $val->quantity ?? '',
                        'unit' => $val->unit ?? '',
                        'position' => $val->position ?? '',
                        'supplier_id' => (int) $val->supplier_id,
                        'consumption' => $val->consumption,
                        'wastage' => $val->wastage,
                        'total' => $val->total,
                        'total_booking' => $val->total_booking,
                        'unit_price' => $val->unit_price,
                        'actual_unit_price' => $val->actual_unit_price,
                        'actual_total_price' => $val->actual_total_price,
                        'total_price' => $val->total_price,
                    ]);
                }
            }

            return response()->json([
                        'status' => 'success',
                        'data' => $budget
                            ], 200);
        } catch (\Throwable $ex) {
            return response()->json([
                        'status' => 'error',
                        'message' => $ex->getMessage()
                            ], 500);
        }
    }

    public function destroy(Request $request) {

        $id = $request->input('id');
        $budget = Budget::find($id);
        if (!$budget) {
            return response()->json(['error' => 'Budget not found.'], 404);
        }

        $budget->items()->delete();
        $budget->delete();

        return response()->json([
                    'status' => 'success',
                    'message' => 'Budget deleted successfully.'
                        ], 200);
    }

    public function toggleStatus(Request $request) {
        try {
            $statusCode = 422;
            $return = [];
            $user_id = $request->user->id;

            $user = \App\Models\User::find($request->user->id);
            $id = $request->input('id');
            $status = $request->input('status');
            $budget = Budget::find($id);
            if (!$budget) {
                return response()->json([
                            'status' => 'error',
                            'main_error_message' => 'Budget not found'
                                ], 404);
            }
            if ($status == "Placed") {
                $budget->placed_by = $user_id;
                $budget->placed_at = date('Y-m-d H:i:s');
                //notification for team leder
                $find_user_team = Team::whereRaw("FIND_IN_SET('$user_id', employees)")->first();
                $team_leader = $find_user_team->team_lead;

                $notification = new \App\Models\Notification;
                $notification->title = "Budget Placed By " . $user->full_name;
                $notification->receiver = $team_leader;
                $notification->url = "/merchandising/budgets-details/" . $budget->id;
                $notification->description = "Please Take Necessary Action";
                $notification->is_read = 0;
                $notification->save();
            } else if ($status == "Confirmed") {
                $budget->confirmed_by = $user_id;
                $budget->confirmed_at = date('Y-m-d H:i:s');

                $notify_users = \App\Models\User::where('department', 16)->where('designation', 25)->get();
                if ($notify_users->isNotEmpty()) {
                    foreach ($notify_users as $notify_user) {
                        $notification = new \App\Models\Notification;
                        $notification->title = "Budget Confirmed By " . $user->full_name;
                        $notification->receiver = $notify_user->id;
                        $notification->url = "/merchandising/budgets-details/" . $budget->id;
                        $notification->description = "Please Take Necessary Action";
                        $notification->is_read = 0;
                        $notification->save();
                    }
                }
            } else if ($status == "Submitted") {
                $budget->submitted_by = $user_id;
                $budget->submitted_at = date('Y-m-d H:i:s');

                $notify_users = \App\Models\User::where('department', 5)->where('company', 4)->get();
                if ($notify_users->isNotEmpty()) {
                    foreach ($notify_users as $notify_user) {
                        $notification = new \App\Models\Notification;
                        $notification->title = "Budget Submitted By " . $user->full_name;
                        $notification->receiver = $notify_user->id;
                        $notification->url = "/merchandising/budgets-details/" . $budget->id;
                        $notification->description = "Please Take Necessary Action";
                        $notification->is_read = 0;
                        $notification->save();
                    }
                }
            } else if ($status == "Checked") {
                $budget->checked_by = $user_id;
                $budget->checked_at = date('Y-m-d H:i:s');

                $notify_users = \App\Models\User::where('department', 4)
                                ->where('company', 4)
                                ->where('designation', 1)->get();
                if ($notify_users->isNotEmpty()) {
                    foreach ($notify_users as $notify_user) {
                        $notification = new \App\Models\Notification;
                        $notification->title = "Budget Checked By " . $user->full_name;
                        $notification->receiver = $notify_user->id;
                        $notification->url = "/merchandising/budgets-details/" . $budget->id;
                        $notification->description = "Please Take Necessary Action";
                        $notification->is_read = 0;
                        $notification->save();
                    }
                }
            } else if ($status == "Cost-Approved") {
                $budget->cost_approved_by = $user_id;
                $budget->cost_approved_at = date('Y-m-d H:i:s');

                $notify_users = \App\Models\User::where('department', 4)
                                ->where('company', 4)
                                ->where('designation', 26)->get();
                if ($notify_users->isNotEmpty()) {
                    foreach ($notify_users as $notify_user) {
                        $notification = new \App\Models\Notification;
                        $notification->title = "Budget Cost-Approved By " . $user->full_name;
                        $notification->receiver = $notify_user->id;
                        $notification->url = "/merchandising/budgets-details/" . $budget->id;
                        $notification->description = "Please Take Necessary Action";
                        $notification->is_read = 0;
                        $notification->save();
                    }
                }
            } else if ($status == "Finalized") {
                $budget->finalized_by = $user_id;
                $budget->finalized_at = date('Y-m-d H:i:s');

                $notify_user = \App\Models\User::where('designation', 12)->first();
                $notification = new \App\Models\Notification;
                $notification->title = "Budget Finalized By " . $user->full_name;
                $notification->receiver = $notify_user->id;
                $notification->url = "/merchandising/budgets-details/" . $budget->id;
                $notification->description = "Please Take Necessary Action";
                $notification->is_read = 0;
                $notification->save();
            } else if ($status == "Approved") {
                $budget->approved_by = $user_id;
                $budget->approved_at = date('Y-m-d H:i:s');

                $notification = new \App\Models\Notification;
                $notification->title = "Budget Approved By " . $user->full_name;
                $notification->receiver = $budget->user_id;
                $notification->url = "/merchandising/budgets-details/" . $budget->id;
                $notification->description = "Please Take Necessary Action";
                $notification->is_read = 0;
                $notification->save();
            } else if ($status == "Rejected") {
                $budget->placed_by = 0;
                $budget->confirmed_by = 0;
                $budget->submitted_by = 0;
                $budget->checked_by = 0;
                $budget->cost_approved_by = 0;
                $budget->finalized_by = 0;
                $budget->approved_by = 0;
                $budget->rejected_by = $user_id;
                $budget->rejected_at = date('Y-m-d H:i:s');
                $budget->placed_at = '';
                $budget->confirmed_at = '';
                $budget->submitted_at = '';
                $budget->checked_at = '';
                $budget->cost_approved_at = '';
                $budget->finalized_at = '';
                $budget->approved_at = '';

                $notification = new \App\Models\Notification;
                $notification->title = "Budget Rejected By " . $user->full_name;
                $notification->receiver = $budget->user_id;
                $notification->url = "/merchandising/budgets-details/" . $budget->id;
                $notification->description = "Please Take Necessary Action";
                $notification->is_read = 0;
                $notification->save();
            }

            $budget->status = $status;
            $budget->save();
            $statusCode = 200;
            $return['data'] = $budget;
            $return['status'] = 'success';
            return $this->response($return, $statusCode);
        } catch (\Throwable $ex) {
            return $this->error(['status' => "error", 'main_error_message' => $ex->getMessage()]);
        }
    }

    public function single_budget_item(Request $request) {
        try {
            $statusCode = 422;
            $return = [];
            $id = $request->input('id');
            $budget_item = BudgetItem::find($id);
            if ($budget_item) {
                $budget_item->already_booking_total = \App\Models\BookingItem::where('budget_item_id', $budget_item->id)->sum('total');
                $budget_item->already_booking_qty = \App\Models\BookingItem::where('budget_item_id', $budget_item->id)->sum('qty');

                $budget_item->left_booking_qty = $budget_item->total_req_qty - $budget_item->already_booking_qty;
                $budget_item->left_booking_total = $budget_item->order_total_cost - $budget_item->already_booking_total;
                $return['data'] = $budget_item;
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

    public function budget_items_via_supplier_budget(Request $request) {
        try {
            $statusCode = 422;
            $return = [];
            $supplier_id = $request->input('supplier_id');
            $budget_id = $request->input('budget_id');
            $budget_items = BudgetItem::where('supplier_id', $supplier_id)->where('budget_id', $budget_id)->get();

            $result = [];
            foreach ($budget_items as $val) {
                $budget = Budget::where('id', $val->budget_id)->first();
                $val->budget_number = $budget->budget_number;
                $item = \App\Models\Item::where('id', $val->item_id)->first();
                $val->item_name = $item->title;
                $purchase = \App\Models\Purchase::where('id', $budget->purchase_id)->first();
                $val->po_number = $purchase->po_number;
                $techpack = \App\Models\Techpack::where('id', $purchase->techpack_id)->first();
                $val->techpack = $techpack->title;
                $val->already_booking_total = \App\Models\BookingItem::where('budget_item_id', $val->id)->where('budget_id', $val->budget_id)->sum('total');
                $val->already_booking_qty = \App\Models\BookingItem::where('budget_item_id', $val->id)->where('budget_id', $val->budget_id)->sum('qty');
                $val->left_booking_qty = $val->total_req_qty - $val->already_booking_qty;
                $val->left_booking_total = $val->order_total_cost - $val->already_booking_total;
                $val->unit_price_limit = $val->unit_price;
                $val->qty = $val->left_booking_qty;
                $val->total = $val->left_booking_total;
                $val->budget_item_id = $val->id;
                $val->remarks = "";
                $val->shade = "";
                $val->tex = "";
                if ($val->already_booking_qty !== $val->total_req_qty && $val->already_booking_total !== $val->order_total_cost) {
                    // Add the item to the result array if conditions don't match
                    $result[] = $val;
                }
            }
            $return['data'] = $result;
            $statusCode = 200;
            $return['status'] = 'success';

            return $this->response($return, $statusCode);
        } catch (\Throwable $ex) {
            return $this->error(['status' => "error", 'main_error_message' => $ex->getMessage()]);
        }
    }
}
