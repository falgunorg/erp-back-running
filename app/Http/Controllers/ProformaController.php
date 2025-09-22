<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Proforma;
use Illuminate\Support\Facades\Validator;
use App\Models\ProformaItem;
use App\Models\ProformaFile;
use App\Models\Team;

class ProformaController extends Controller {

    public function admin_index(Request $request) {
        try {
            $statusCode = 422;
            $return = [];
            $from_date = $request->input('from_date');
            $to_date = $request->input('to_date');
            $status = $request->input('status');
            $supplier_id = $request->input('supplier_id');
            $purchase_contract_id = $request->input('purchase_contract_id');
            $num_of_row = $request->input('num_of_row');
            $filter_items = $request->input('filter_items');
            $query = Proforma::orderBy('created_at', 'desc');
            // Apply filters
            if ($status) {
                $query->where('status', $status);
            }
            if ($supplier_id) {
                $query->where('supplier_id', $supplier_id);
            }
            if ($purchase_contract_id) {
                $query->where('purchase_contract_id', $purchase_contract_id);
            }

            if ($filter_items) {
                $query->whereIn('id', $filter_items);
            }
            // Apply date range filter if both "from_date" and "to_date" are provided
            if ($from_date && $to_date) {
                $query->whereBetween('created_at', [$from_date, $to_date]);
            }
            $proformas = $query->take($num_of_row)->get();
            foreach ($proformas as $val) {
                $user = \App\Models\User::where('id', $val->user_id)->first();
                $val->user = $user->full_name;
                $supplier = \App\Models\Supplier::where('id', $val->supplier_id)->first();
                $val->supplier = $supplier->company_name;
                $contract = \App\Models\PurchaseContract::find($val->purchase_contract_id);
                $val->contract_number = $contract->title;
                $buyer = \App\Models\Buyer::where('id', $contract->buyer_id)->first();
                $val->buyer = $buyer->name;
            }
            $return['data'] = $proformas;
            $statusCode = 200;

            return $this->response($return, $statusCode);
        } catch (\Throwable $ex) {
            return $this->error(['status' => 'error', 'main_error_message' => $ex->getMessage()]);
        }
    }

    public function index(Request $request) {
        try {
            $statusCode = 422;
            $return = [];
            $from_date = $request->input('from_date');
            $to_date = $request->input('to_date');
            $status = $request->input('status');
            $supplier_id = $request->input('supplier_id');
            $purchase_contract_id = $request->input('purchase_contract_id');
            $num_of_row = $request->input('num_of_row');
            $filter_items = $request->input('filter_items');
            $user_id = $request->user->id;
            $department = $request->input('department');
            $designation = $request->input('designation');

//all Proformas 
            $proforma_all = Proforma::orderBy('created_at', 'desc')->get();
// Query builder instance
            $query = Proforma::orderBy('updated_at', 'desc');
            if ($department && $designation) {
                if ($department == "Merchandising" && $designation == "Merchandiser") {
                    $query->where('user_id', $user_id);
                } else if ($department == "Merchandising" && $designation == "Sr. Merchandiser") {
                    $query->where('user_id', $user_id);
                } else if ($department == "Merchandising" && $designation == "Asst. Merchandiser") {
                    $query->where('user_id', $user_id);
                } else if ($department == "Merchandising" && $designation == "Assistant Manager") {
                    $find_user_team = Team::whereRaw("FIND_IN_SET('$user_id', employees)")->first();
                    $return['Team'] = $find_user_team;
                    $team_users = explode(',', $find_user_team->employees);
                    $query->whereIn('user_id', $team_users)->whereNotIn('status', ['Pending', 'Rejected']); //Placed
                } else if ($department == "Merchandising" && $designation == "Deputy General Manager") {
                    $query->whereNotIn('status', ['Pending', 'Placed', 'Rejected']); //Confirmed
                } else if ($department == "Audit" && $designation == "Manager") {
                    $query->whereNotIn('status', ['Pending', 'Placed', 'Confirmed', 'Rejected']); //Submitted
                } else if ($department == "Accounts & Finance" && $designation == "Manager") {
                    $query->whereNotIn('status', ['Pending', 'Placed', 'Confirmed', 'Submitted', 'Rejected']); //Checked
                } else if ($department == "Accounts & Finance" && $designation == "General Manager") {
                    $query->whereNotIn('status', ['Pending', 'Placed', 'Confirmed', 'Submitted', 'Checked', 'Rejected']); //Cost-Approved
                } else if ($department == "Management" && $designation == "Managing Director") {
                    $query->whereNotIn('status', ['Pending', 'Placed', 'Confirmed', 'Submitted', 'Cost-Approved', 'Checked', 'Rejected']); //Finalized
                } else if ($department == "Commercial" && $designation == "Asst. General Manager") {
                    $query->whereNotIn('status', ['Pending', 'Placed', 'Confirmed', 'Submitted', 'Checked', 'Finalized', 'Cost-Approved', 'Rejected']); // Approved
                }
            }
            // Apply filters
            if ($status) {
                $query->where('status', $status);
            }
            if ($supplier_id) {
                $query->where('supplier_id', $supplier_id);
            }
            if ($purchase_contract_id) {
                $query->where('purchase_contract_id', $purchase_contract_id);
            }
            if ($filter_items) {
                $query->whereIn('id', $filter_items);
            }
            // Apply date range filter if both "from_date" and "to_date" are provided
            if ($from_date && $to_date) {
                $query->whereBetween('created_at', [$from_date, $to_date]);
            }
            $proformas = $query->take($num_of_row)->get();
            foreach ($proformas as $val) {
                $user = \App\Models\User::where('id', $val->user_id)->first();
                $val->user = $user->full_name;
                $supplier = \App\Models\Supplier::where('id', $val->supplier_id)->first();
                $val->supplier = $supplier->company_name;
                $contract = \App\Models\PurchaseContract::find($val->purchase_contract_id);
                $val->contract_number = $contract->title;
                $buyer = \App\Models\Buyer::where('id', $contract->buyer_id)->first();
                $val->buyer = $buyer->name;
            }
            $return['data'] = $proformas;
            $return['user'] = $request->user;
            $return['all_proformas'] = $proforma_all;
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
            $proforma_items = json_decode($request->input('proforma_items'));

            $validator = Validator::make($request->all(), [
                        'purchase_contract_id' => 'required',
                        'supplier_id' => 'required',
                        'company_id' => 'required',
                        'title' => 'required|unique:proformas,title',
                        'currency' => 'required',
                        'issued_date' => 'required',
                        'delivery_date' => 'required',
                        'pi_validity' => 'required',
                        'net_weight' => 'required',
                        'gross_weight' => 'required',
                        'freight_charge' => 'required',
                        'description' => 'nullable',
                        'bank_account_name' => 'required',
                        'bank_account_number' => 'required',
                        'bank_brunch_name' => 'required',
                        'bank_name' => 'required',
                        'bank_address' => 'nullable',
                        'bank_swift_code' => 'required',
            ]);

            if ($validator->fails()) {
                return response()->json([
                            'errors' => $validator->errors()
                                ], 422);
            }

            $proforma = new Proforma([
                'user_id' => $request->user->id,
                'purchase_contract_id' => $request->input('purchase_contract_id'),
                'supplier_id' => $request->input('supplier_id'),
                'company_id' => $request->input('company_id'),
                'title' => $request->input('title'),
                'currency' => $request->input('currency'),
                'issued_date' => $request->input('issued_date'),
                'delivery_date' => $request->input('delivery_date'),
                'pi_validity' => $request->input('pi_validity'),
                'net_weight' => $request->input('net_weight'),
                'gross_weight' => $request->input('gross_weight'),
                'freight_charge' => $request->input('freight_charge'),
                'description' => $request->input('description'),
                'bank_account_name' => $request->input('bank_account_name'),
                'bank_account_number' => $request->input('bank_account_number'),
                'bank_brunch_name' => $request->input('bank_brunch_name'),
                'bank_name' => $request->input('bank_name'),
                'bank_address' => $request->input('bank_address'),
                'bank_swift_code' => $request->input('bank_swift_code'),
                'status' => "Pending",
            ]);

            if ($proforma->save()) {
                foreach ($proforma_items as $val) {
                    $item = new ProformaItem;
                    $item->proforma_id = $proforma->id;
                    $item->booking_id = $val->booking_id;
                    $item->booking_item_id = $val->booking_item_id;
                    $item->budget_id = $val->budget_id;
                    $item->budget_item_id = $val->budget_item_id;
                    $item->item_id = $val->item_id;
                    $item->description = $val->description;
                    $item->unit = $val->unit;
                    $item->qty = $val->qty;
                    $item->hscode = $val->hs_code_id;
                    $item->unit_price = $val->unit_price;
                    $item->total = $val->total;
                    $item->save();
                }
            }

            if (request()->hasFile('attatchments')) {
                $files = request()->file('attatchments');
                foreach ($files as $file) {
                    $upload = new ProformaFile();
                    $upload->proforma_id = $proforma->id;
                    $public_path = public_path();
                    $path = $public_path . '/' . "proformas";
                    $pathinfo = pathinfo($file->getClientOriginalName());
                    $basename = strtolower(str_replace(' ', '_', $pathinfo['filename']));
                    $extension = strtolower($pathinfo['extension']);
                    $file_name = $basename . '.' . $extension;
                    $finalpath = $path . '/' . $file_name;
                    if (file_exists($finalpath)) {
                        $file_name = $basename . '_' . time() . '.' . $extension;
                        $finalpath = $path . '/' . $file_name;
                    }

                    if ($file->move($path, $file_name)) {
                        $upload->filename = $file_name;
                        $upload->save();
                    }
                }
            }
            $total_amount = ProformaItem::where('proforma_id', $proforma->id)->sum('total');
            $proforma->total = $total_amount;
            $proforma->save();
            $statusCode = 200;
            $return['data'] = $proforma;
            $return['status'] = 'success';
            return $this->response($return, $statusCode);
        } catch (\Throwable $ex) {
            return $this->error(['status' => "error", 'main_error_message' => $ex->getMessage()]);
        }
    }

    public function store_auto(Request $request) {
        try {
            $statusCode = 422;
            $return = [];
            $proforma_items = json_decode($request->input('proforma_items'));

            $validator = Validator::make($request->all(), [
                        'purchase_contract_id' => 'required',
                        'supplier_id' => 'required',
                        'company_id' => 'required',
                        'title' => 'required|unique:proformas,title',
                        'currency' => 'required',
                        'issued_date' => 'required',
                        'delivery_date' => 'required',
                        'pi_validity' => 'required',
                        'net_weight' => 'required',
                        'gross_weight' => 'required',
                        'freight_charge' => 'required',
                        'description' => 'nullable',
                        'bank_account_name' => 'required',
                        'bank_account_number' => 'required',
                        'bank_brunch_name' => 'required',
                        'bank_name' => 'required',
                        'bank_address' => 'nullable',
                        'bank_swift_code' => 'required',
            ]);

            if ($validator->fails()) {
                return response()->json([
                            'errors' => $validator->errors()
                                ], 422);
            }

            $proforma = new Proforma([
                'user_id' => $request->user->id,
                'purchase_contract_id' => $request->input('purchase_contract_id'),
                'supplier_id' => $request->input('supplier_id'),
                'company_id' => $request->input('company_id'),
                'title' => $request->input('title'),
                'currency' => $request->input('currency'),
                'issued_date' => $request->input('issued_date'),
                'delivery_date' => $request->input('delivery_date'),
                'pi_validity' => $request->input('pi_validity'),
                'net_weight' => $request->input('net_weight'),
                'gross_weight' => $request->input('gross_weight'),
                'freight_charge' => $request->input('freight_charge'),
                'description' => $request->input('description'),
                'bank_account_name' => $request->input('bank_account_name'),
                'bank_account_number' => $request->input('bank_account_number'),
                'bank_brunch_name' => $request->input('bank_brunch_name'),
                'bank_name' => $request->input('bank_name'),
                'bank_address' => $request->input('bank_address'),
                'bank_swift_code' => $request->input('bank_swift_code'),
                'status' => "Pending",
            ]);

            if ($proforma->save()) {
                foreach ($proforma_items as $val) {
                    $item = new ProformaItem;
                    $item->proforma_id = $proforma->id;
                    $item->booking_id = $val->booking_id;
                    $item->booking_item_id = $val->id;
                    $item->budget_id = $val->budget_id;
                    $item->budget_item_id = $val->budget_item_id;
                    $item->item_id = $val->item_id;
                    $item->description = $val->description;
                    $item->unit = $val->unit;
                    $item->qty = $val->qty;
                    $item->hscode = $val->hs_code_id;
                    $item->unit_price = $val->unit_price;
                    $item->total = $val->total;
                    $item->save();
                }
            }

            if (request()->hasFile('attatchments')) {
                $files = request()->file('attatchments');
                foreach ($files as $file) {
                    $upload = new ProformaFile();
                    $upload->proforma_id = $proforma->id;
                    $public_path = public_path();
                    $path = $public_path . '/' . "proformas";
                    $pathinfo = pathinfo($file->getClientOriginalName());
                    $basename = strtolower(str_replace(' ', '_', $pathinfo['filename']));
                    $extension = strtolower($pathinfo['extension']);
                    $file_name = $basename . '.' . $extension;
                    $finalpath = $path . '/' . $file_name;
                    if (file_exists($finalpath)) {
                        $file_name = $basename . '_' . time() . '.' . $extension;
                        $finalpath = $path . '/' . $file_name;
                    }

                    if ($file->move($path, $file_name)) {
                        $upload->filename = $file_name;
                        $upload->save();
                    }
                }
            }
            $total_amount = ProformaItem::where('proforma_id', $proforma->id)->sum('total');
            $proforma->total = $total_amount;
            $proforma->save();
            $statusCode = 200;
            $return['data'] = $proforma;
            $return['status'] = 'success';
            return $this->response($return, $statusCode);
        } catch (\Throwable $ex) {
            return $this->error(['status' => "error", 'main_error_message' => $ex->getMessage()]);
        }
    }

//    It will need when function applied
//    public function admin_proforma_approve(Request $request) {
//        try {
//            $statusCode = 422;
//            $return = [];
//            $id = $request->input('id');
//            $proforma = Proforma::find($id);
//            if ($proforma) {
//                $proforma->status = 'Approved';
//                $proforma->save();
//                $return['data'] = $proforma;
//                $statusCode = 200;
//                $return['status'] = 'success';
//            } else {
//                $return['status'] = 'error';
//            }
//            return $this->response($return, $statusCode);
//        } catch (\Throwable $ex) {
//            return $this->error(['status' => "error", 'main_error_message' => $ex->getMessage()]);
//        }
//    }

    public function show(Request $request) {
        try {
            $statusCode = 422;
            $return = [];
            $id = $request->input('id');
            $proforma = Proforma::find($id);
            if ($proforma) {
                $user = \App\Models\User::where('id', $proforma->user_id)->first();
                $proforma->user = $user->full_name;
                $proforma->user_staff_id = $user->staff_id;
                $supplier = \App\Models\Supplier::where('id', $proforma->supplier_id)->first();
                $proforma->supplier = $supplier->company_name;
                $proforma->supplier_address = $supplier->address;
                $proforma->supplier_city = $supplier->state;
                $proforma->supplier_country = $supplier->country;
                $proforma->supplier_attention = $supplier->attention_person;
                $proforma->supplier_contact = $supplier->mobile_number;
                $contract = \App\Models\PurchaseContract::find($proforma->purchase_contract_id);
                $proforma->contract_number = $contract->title;
                $proforma->tag_number = $contract->tag_number;
                $buyer = \App\Models\Buyer::where('id', $contract->buyer_id)->first();
                $proforma->buyer = $buyer->name;
                $company = \App\Models\Company::where('id', $proforma->company_id)->first();
                $proforma->company = $company->title;
                $proforma->company_address = $company->address;
                $attachments = ProformaFile::where('proforma_id', $proforma->id)->get();
                foreach ($attachments as $val) {
                    $val->file_source = url('') . '/proformas/' . $val->filename;
                }
                $proforma->attachments = $attachments;
                $proforma_items = ProformaItem::where('proforma_id', $proforma->id)->get();
                foreach ($proforma_items as $val) {
                    $item = \App\Models\Item::where('id', $val->item_id)->first();
                    $val->title = $item->title;
                    $hscode = \App\Models\Hscode::where('id', $val->hscode)->first();
                    $val->code_8 = $hscode->code_8;
                    $val->code_10 = $hscode->code_10;
                    $val->hs_description = $hscode->description;
                    $val->hs = $hscode->hs;

                    $booking_item = \App\Models\BookingItem::where('id', $val->booking_item_id)->first();

                    $val->color = $booking_item->color;
                    $val->size = $booking_item->size;
                    $val->shade = $booking_item->shade;
                    $val->tex = $booking_item->tex;

                    $bookingItems = \App\Models\BookingItem::where('booking_id', $val->booking_id)->get();

                    foreach ($bookingItems as $bookingItem) {
                        $budget = \App\Models\Budget::where('id', $bookingItem->budget_id)->first();
                        $purchase = \App\Models\Purchase::where('id', $budget->purchase_id)->first();
                        $contract = \App\Models\PurchaseContract::where('id', $purchase->contract_id)->first();
                        $bookingItem->buyer_id = $contract->buyer_id;
                        $booking = \App\Models\Booking::where('id', $bookingItem->booking_id)->first();
                        $bookingItem->booking_user_id = $booking->user_id;
                        $bookingItem->supplier_id = $booking->supplier_id;
                        $bookingItem->company_id = $booking->company_id;
                        $techpack = \App\Models\Techpack::where('id', $purchase->techpack_id)->first();
                        $bookingItem->techpack = $techpack->title;
                        $bookingItem->po_number = $purchase->po_number;
                        $bookingItem->budget_number = $budget->budget_number;
                        $bookingItem->techpack_id = $techpack->id;
                        $budget_item = \App\Models\BudgetItem::where('id', $bookingItem->budget_item_id)->first();
                        $item = \App\Models\Item::where('id', $budget_item->item_id)->first();
                        $bookingItem->item_id = $item->id;
                        $bookingItem->item_name = $item->title;

//             FOR ADD PI ITEMS
                        $bookingItem->already_added_pi = \App\Models\ProformaItem::where('booking_item_id', $bookingItem->id)->sum('total');
                        $bookingItem->already_added_pi_qty = \App\Models\ProformaItem::where('booking_item_id', $bookingItem->id)->sum('qty');
                        $bookingItem->left_pi_qty = $bookingItem->qty - $bookingItem->already_added_pi_qty;
                        $bookingItem->left_pi_total = $bookingItem->left_pi_qty * $bookingItem->unit_price;
                    }

                    $val->bookingItems = $bookingItems;
                }
                $proforma->proforma_items = $proforma_items;

//                Signatures
//                mr
                $placed_by = \App\Models\User::where('id', $proforma->placed_by)->first();
                if ($placed_by) {
                    $proforma->placed_by_sign = url('') . '/signs/' . $placed_by->sign;
                }

//                mr team lead
                $confirmed_by = \App\Models\User::where('id', $proforma->confirmed_by)->first();
                if ($confirmed_by) {
                    $proforma->confirmed_by_sign = url('') . '/signs/' . $confirmed_by->sign;
                }



//                mr head
                $submitted_by = \App\Models\User::where('id', $proforma->submitted_by)->first();

                if ($submitted_by) {
                    $proforma->submitted_by_sign = url('') . '/signs/' . $submitted_by->sign;
                }


//                Audit

                $checked_by = \App\Models\User::where('id', $proforma->checked_by)->first();
                if ($checked_by) {
                    $proforma->checked_by_sign = url('') . '/signs/' . $checked_by->sign;
                }


                //          Finance
                $cost_approved_by = \App\Models\User::where('id', $proforma->cost_approved_by)->first();
                if ($cost_approved_by) {
                    $proforma->cost_approved_by_sign = url('') . '/signs/' . $cost_approved_by->sign;
                }

                //          GM
                $finalized_by = \App\Models\User::where('id', $proforma->finalized_by)->first();
                if ($finalized_by) {
                    $proforma->finalized_by_sign = url('') . '/signs/' . $finalized_by->sign;
                }


                //     MD
                $approved_by = \App\Models\User::where('id', $proforma->approved_by)->first();
                if ($approved_by) {
                    $proforma->approved_by_sign = url('') . '/signs/' . $approved_by->sign;
                }

                // Commercial
                $received_by = \App\Models\User::where('id', $proforma->received_by)->first();
                if ($received_by) {
                    $proforma->received_by_sign = url('') . '/signs/' . $received_by->sign;
                }


                $return['data'] = $proforma;
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
            $proforma_items = json_decode($request->input('proforma_items'));
            $id = $request->input('id');
            $validator = Validator::make($request->all(), [
                        'purchase_contract_id' => 'required',
                        'supplier_id' => 'required',
                        'company_id' => 'required',
                        'title' => 'required|unique:proformas,title,' . $id,
                        'currency' => 'required',
                        'issued_date' => 'required',
                        'delivery_date' => 'required',
                        'pi_validity' => 'required',
                        'net_weight' => 'required',
                        'gross_weight' => 'required',
                        'freight_charge' => 'required',
                        'description' => 'nullable',
                        'bank_account_name' => 'required',
                        'bank_account_number' => 'required',
                        'bank_brunch_name' => 'required',
                        'bank_name' => 'required',
                        'bank_address' => 'nullable',
                        'bank_swift_code' => 'required',
            ]);

            if ($validator->fails()) {
                return response()->json([
                            'errors' => $validator->errors()
                                ], 422);
            }

            $proforma = Proforma::find($id);

            if (!$proforma) {
                return response()->json([
                            'status' => 'error',
                            'main_error_message' => 'Proforma not found'
                                ], 404);
            }

            $proforma->update([
                'user_id' => $request->user->id,
                'purchase_contract_id' => $request->input('purchase_contract_id'),
                'supplier_id' => $request->input('supplier_id'),
                'company_id' => $request->input('company_id'),
                'title' => $request->input('title'),
                'currency' => $request->input('currency'),
                'issued_date' => $request->input('issued_date'),
                'delivery_date' => $request->input('delivery_date'),
                'pi_validity' => $request->input('pi_validity'),
                'net_weight' => $request->input('net_weight'),
                'gross_weight' => $request->input('gross_weight'),
                'freight_charge' => $request->input('freight_charge'),
                'description' => $request->input('description'),
                'bank_account_name' => $request->input('bank_account_name'),
                'bank_account_number' => $request->input('bank_account_number'),
                'bank_brunch_name' => $request->input('bank_brunch_name'),
                'bank_name' => $request->input('bank_name'),
                'bank_address' => $request->input('bank_address'),
                'bank_swift_code' => $request->input('bank_swift_code'),
                'status' => "Pending",
            ]);
            ProformaItem::where('proforma_id', $proforma->id)->delete();
            foreach ($proforma_items as $val) {
                $item = new ProformaItem;
                $item->proforma_id = $proforma->id;
                $item->booking_id = $val->booking_id;
                $item->booking_item_id = $val->booking_item_id;
                $item->budget_id = $val->budget_id;
                $item->budget_item_id = $val->budget_item_id;
                $item->item_id = $val->item_id;
                $item->description = $val->description;
                $item->unit = $val->unit;
                $item->qty = $val->qty;
                $item->hscode = $val->hs_code_id;
                $item->unit_price = $val->unit_price;
                $item->total = $val->total;
                $item->save();
            }

            if (request()->hasFile('attatchments')) {
                $files = request()->file('attatchments');
                foreach ($files as $file) {
                    $upload = new ProformaFile();
                    $upload->proforma_id = $proforma->id;
                    $public_path = public_path();
                    $path = $public_path . '/' . "proformas";
                    $pathinfo = pathinfo($file->getClientOriginalName());
                    $basename = strtolower(str_replace(' ', '_', $pathinfo['filename']));
                    $extension = strtolower($pathinfo['extension']);
                    $file_name = $basename . '.' . $extension;
                    $finalpath = $path . '/' . $file_name;
                    if (file_exists($finalpath)) {
                        $file_name = $basename . '_' . time() . '.' . $extension;
                        $finalpath = $path . '/' . $file_name;
                    }

                    if ($file->move($path, $file_name)) {
                        $upload->filename = $file_name;
                        $upload->save();
                    }
                }
            }
            $total_amount = ProformaItem::where('proforma_id', $proforma->id)->sum('total');
            $proforma->total = $total_amount;
            $proforma->save();
            $statusCode = 200;
            $return['data'] = $proforma;
            $return['status'] = 'success';
            return $this->response($return, $statusCode);
        } catch (\Throwable $ex) {
            return $this->error(['status' => "error", 'main_error_message' => $ex->getMessage()]);
        }
    }

    public function toggleStatus(Request $request) {
        try {
            $statusCode = 422;
            $return = [];

            $user_id = $request->user->id;
            $user = \App\Models\User::find($request->user->id);
            $id = $request->input('id');
            $status = $request->input('status');
            $proforma = Proforma::find($id);
            if (!$proforma) {
                return response()->json([
                            'status' => 'error',
                            'main_error_message' => 'Proforma not found'
                                ], 404);
            }
            if ($status == "Placed") {
                $proforma->placed_by = $user_id;
                $proforma->placed_at = date('Y-m-d H:i:s');

                $find_user_team = Team::whereRaw("FIND_IN_SET('$user_id', employees)")->first();
                $team_leader = $find_user_team->team_lead;
                $notification = new \App\Models\Notification;
                $notification->title = "PI Placed By " . $user->full_name;
                $notification->receiver = $team_leader;
                $notification->url = "/merchandising/proformas-details/" . $proforma->id;
                $notification->description = "Please Take Necessary Action";
                $notification->is_read = 0;
                $notification->save();
            } else if ($status == "Confirmed") {
                $proforma->confirmed_by = $user_id;
                $proforma->confirmed_at = date('Y-m-d H:i:s');

                $notify_users = \App\Models\User::where('department', 16)->where('designation', 25)->get();
                if ($notify_users->isNotEmpty()) {
                    foreach ($notify_users as $notify_user) {
                        $notification = new \App\Models\Notification;
                        $notification->title = "PI Confirmed By " . $user->full_name;
                        $notification->receiver = $notify_user->id;
                        $notification->url = "/merchandising/proformas-details/" . $proforma->id;
                        $notification->description = "Please Take Necessary Action";
                        $notification->is_read = 0;
                        $notification->save();
                    }
                }
                $notification = new \App\Models\Notification;
                $notification->title = "Your PI is Confirmed By " . $user->full_name;
                $notification->receiver = $proforma->user_id;
                $notification->url = "/merchandising/proformas-details/" . $proforma->id;
                $notification->description = "Please Take Necessary Action";
                $notification->is_read = 0;
                $notification->save();
            } else if ($status == "Submitted") {
                $proforma->submitted_by = $user_id;
                $proforma->submitted_at = date('Y-m-d H:i:s');
                $notify_users = \App\Models\User::where('department', 5)->where('company', 4)->get();
                if ($notify_users->isNotEmpty()) {
                    foreach ($notify_users as $notify_user) {
                        $notification = new \App\Models\Notification;
                        $notification->title = "PI Submitted By " . $user->full_name;
                        $notification->receiver = $notify_user->id;
                        $notification->url = "/merchandising/proformas-details/" . $proforma->id;
                        $notification->description = "Please Take Necessary Action";
                        $notification->is_read = 0;
                        $notification->save();
                    }
                }
                $notification = new \App\Models\Notification;
                $notification->title = "Your PI is Submitted By " . $user->full_name;
                $notification->receiver = $proforma->user_id;
                $notification->url = "/merchandising/proformas-details/" . $proforma->id;
                $notification->description = "Please Take Necessary Action";
                $notification->is_read = 0;
                $notification->save();
            } else if ($status == "Checked") {
                $proforma->checked_by = $user_id;
                $proforma->checked_at = date('Y-m-d H:i:s');

                $notify_users = \App\Models\User::where('department', 4)
                                ->where('company', 4)
                                ->where('designation', 1)->get();
                if ($notify_users->isNotEmpty()) {
                    foreach ($notify_users as $notify_user) {
                        $notification = new \App\Models\Notification;
                        $notification->title = "PI Checked By " . $user->full_name;
                        $notification->receiver = $notify_user->id;
                        $notification->url = "/merchandising/proformas-details/" . $proforma->id;
                        $notification->description = "Please Take Necessary Action";
                        $notification->is_read = 0;
                        $notification->save();
                    }
                }

                $notification = new \App\Models\Notification;
                $notification->title = "Your PI is Checked By " . $user->full_name;
                $notification->receiver = $proforma->user_id;
                $notification->url = "/merchandising/proformas-details/" . $proforma->id;
                $notification->description = "Please Take Necessary Action";
                $notification->is_read = 0;
                $notification->save();
            } else if ($status == "Cost-Approved") {
                $proforma->cost_approved_by = $user_id;
                $proforma->cost_approved_at = date('Y-m-d H:i:s');

                $notify_users = \App\Models\User::where('department', 4)
                                ->where('company', 4)
                                ->where('designation', 26)->get();
                if ($notify_users->isNotEmpty()) {
                    foreach ($notify_users as $notify_user) {
                        $notification = new \App\Models\Notification;
                        $notification->title = "PI Cost-Approved By " . $user->full_name;
                        $notification->receiver = $notify_user->id;
                        $notification->url = "/merchandising/proformas-details/" . $proforma->id;
                        $notification->description = "Please Take Necessary Action";
                        $notification->is_read = 0;
                        $notification->save();
                    }
                }

                $notification = new \App\Models\Notification;
                $notification->title = "Your PI Cost-Approved By " . $user->full_name;
                $notification->receiver = $proforma->user_id;
                $notification->url = "/merchandising/proformas-details/" . $proforma->id;
                $notification->description = "Please Take Necessary Action";
                $notification->is_read = 0;
                $notification->save();
            } else if ($status == "Finalized") {
                $proforma->finalized_by = $user_id;
                $proforma->finalized_at = date('Y-m-d H:i:s');

                $notify_user = \App\Models\User::where('designation', 12)->first();
                $notification = new \App\Models\Notification;
                $notification->title = "PI Finalized By " . $user->full_name;
                $notification->receiver = $notify_user->id;
                $notification->url = "/merchandising/proformas-details/" . $proforma->id;
                $notification->description = "Please Take Necessary Action";
                $notification->is_read = 0;
                $notification->save();

                //notify users 

                $notification_two = new \App\Models\Notification;
                $notification_two->title = "Your PI Finalized By " . $user->full_name;
                $notification_two->receiver = $proforma->user_id;
                $notification_two->url = "/merchandising/proformas-details/" . $proforma->id;
                $notification_two->description = "Please Take Necessary Action";
                $notification_two->is_read = 0;
                $notification_two->save();
            } else if ($status == "Approved") {
                $proforma->approved_by = $user_id;
                $proforma->approved_at = date('Y-m-d H:i:s');

                $notify_users = \App\Models\User::where('department', 6)
                                ->where('company', 4)
                                ->where('designation', 23)->get();
                if ($notify_users->isNotEmpty()) {
                    foreach ($notify_users as $notify_user) {
                        $notification = new \App\Models\Notification;
                        $notification->title = "PI Approved By " . $user->full_name;
                        $notification->receiver = $notify_user->id;
                        $notification->url = "/merchandising/proformas-details/" . $proforma->id;
                        $notification->description = "Please Take Necessary Action";
                        $notification->is_read = 0;
                        $notification->save();
                    }
                }
                $notification_two = new \App\Models\Notification;
                $notification_two->title = "PI Approved By " . $user->full_name;
                $notification_two->receiver = $proforma->user_id;
                $notification_two->url = "/merchandising/proformas-details/" . $proforma->id;
                $notification_two->description = "Please Take Necessary Action";
                $notification_two->is_read = 0;
                $notification_two->save();
            } else if ($status == "Received") {
                $proforma->received_by = $user_id;
                $proforma->received_at = date('Y-m-d H:i:s');

                $notification = new \App\Models\Notification;
                $notification->title = "PI Received By " . $user->full_name;
                $notification->receiver = $proforma->user_id;
                $notification->url = "/merchandising/proformas-details/" . $proforma->id;
                $notification->description = "Please Take Necessary Action";
                $notification->is_read = 0;
                $notification->save();
            } else if ($status == "BTB-Submitted") {
                $proforma->received_by = $user_id;
                $proforma->btb_submit_at = date('Y-m-d H:i:s');
            } else if ($status == "Rejected") {
                $proforma->placed_by = 0;
                $proforma->confirmed_by = 0;
                $proforma->submitted_by = 0;
                $proforma->checked_by = 0;
                $proforma->cost_approved_by = 0;
                $proforma->finalized_by = 0;
                $proforma->approved_by = 0;
                $proforma->received_by = 0;
                $proforma->btb_submit_by = 0;
                $proforma->rejected_by = $user_id;
                $proforma->rejected_at = date('Y-m-d H:i:s');
                $proforma->placed_at = '';
                $proforma->confirmed_at = '';
                $proforma->submitted_at = '';
                $proforma->checked_at = '';
                $proforma->cost_approved_at = '';
                $proforma->finalized_at = '';
                $proforma->approved_at = '';
                $proforma->received_at = '';
                $proforma->btb_submit_at = '';
                //notification

                $notification = new \App\Models\Notification;
                $notification->title = "PI Rejected By " . $user->full_name;
                $notification->receiver = $proforma->user_id;
                $notification->url = "/merchandising/proformas-details/" . $proforma->id;
                $notification->description = "Please Take Necessary Action";
                $notification->is_read = 0;
                $notification->save();
            }

            $proforma->status = $status;
            $proforma->save();
            $statusCode = 200;
            $return['data'] = $proforma;
            $return['status'] = 'success';
            return $this->response($return, $statusCode);
        } catch (\Throwable $ex) {
            return $this->error(['status' => "error", 'main_error_message' => $ex->getMessage()]);
        }
    }

    public function destroy(Request $request) {
        try {
            $statusCode = 200;
            $return = [];

            $id = $request->input('id');

            // Find the Proforma by its ID
            $proforma = Proforma::findOrFail($id);

            // Delete associated ProformaItems

            $detele_items = ProformaItem::where('proforma_id', $proforma->id)->delete();

            if ($detele_items) {
                $proforma_files = ProformaFile::where('proforma_id', $proforma->id)->get();
                // Unlink and delete attached files
                foreach ($proforma_files as $file) {
                    $filePath = public_path('proformas/' . $file->filename);
                    if (file_exists($filePath)) {
                        unlink($filePath);
                    }
                    $file->delete();
                }
                // Delete the Proforma itself
                $proforma->delete();
            }
            $return['status'] = 'success';
            $return['message'] = 'Proforma and its associated items and files have been successfully deleted.';
            return $this->response($return, $statusCode);
        } catch (\Throwable $ex) {
            return $this->error(['status' => "error", 'main_error_message' => $ex->getMessage()]);
        }
    }

}
