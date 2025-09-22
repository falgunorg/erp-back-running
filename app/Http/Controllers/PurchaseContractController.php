<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\PurchaseContract;
use Illuminate\Support\Facades\Validator;
use App\Models\PurchaseItem;

class PurchaseContractController extends Controller {

    public function index(Request $request) {
        try {
            $statusCode = 422;
            $return = [];
            $buyer_id = $request->input('buyer_id');
            $company_id = $request->input('company_id');
            $from_date = $request->input('from_date');
            $to_date = $request->input('to_date');
            $num_of_row = $request->input('num_of_row');

            $query = PurchaseContract::orderBy('created_at', 'desc');

            // Apply filters
            if ($from_date && $to_date) {
                $query->whereBetween('order_date', [$from_date, $to_date]);
            }

            if ($buyer_id) {
                $query->where('buyer_id', $buyer_id);
            }

            if ($company_id) {
                $query->where('company_id', $company_id);
            }

            $purchase_contracts = $query->take($num_of_row)->get();

            if ($purchase_contracts) {
                foreach ($purchase_contracts as $val) {
                    $company = \App\Models\Company::where('id', $val->company_id)->first();
                    $val->company = $company->title;
                    $buyer = \App\Models\Buyer::where('id', $val->buyer_id)->first();
                    $val->buyer = $buyer->name;
                    $purchases = \App\Models\Purchase::where('contract_id', $val->id)->get();
                    $val->purchases = $purchases;
                    $val->total_qty = \App\Models\Purchase::where('contract_id', $val->id)->sum('total_qty');
                    $val->total_amount = \App\Models\Purchase::where('contract_id', $val->id)->sum('total_amount');
                }
                $return['data'] = $purchase_contracts;
                $statusCode = 200;
            } else {
                $return['status'] = 'error';
            }
            return $this->response($return, $statusCode);
        } catch (\Throwable $ex) {
            return $this->error(['status' => 'error', 'main_error_message' => $ex->getMessage()]);
        }
    }

    public function public_index(Request $request) {

        $statusCode = 422;
        $contracts = PurchaseContract::orderBy('created_at', 'desc')->get();
        $return['data'] = $contracts;
        $statusCode = 200;
        return $this->response($return, $statusCode);
    }

    public function store(Request $request) {
        try {
            $statusCode = 422;
            $return = [];

            $validatedData = $request->validate([
                'buyer_id' => 'required',
                'company_id' => 'required',
                'season' => 'required',
                'year' => 'required',
                'currency' => 'required',
                'title' => 'required',
                'pcc_avail' => 'nullable',
                'issued_date' => 'nullable',
                'shipment_date' => 'nullable',
                'expiry_date' => 'nullable',
            ]);

            $user_id = $request->user->id;

            $purchase_contract = new PurchaseContract($validatedData);
            $purchase_contract->user_id = $user_id;

            if ($purchase_contract->save()) {
                // Construct the tag_number
                $buyer_id = $purchase_contract->buyer_id;
                $company_id = $purchase_contract->company_id;
                $season = $purchase_contract->season;
                $year = $purchase_contract->year;
                $serial_number = $purchase_contract->id;

                $purchase_contract->tag_number = $buyer_id . '/' . $company_id . '/' . $season . '/' . $year . '/' . $serial_number;

                // Save the updated purchase_contract
                if ($purchase_contract->save()) {
                    $return['data'] = $purchase_contract;
                    $statusCode = 200;
                    $return['status'] = 'success';
                } else {
                    $return['errors']['main_error_message'] = 'Error occurred while saving the purchase contract with updated tag number.';
                    $return['status'] = 'error';
                }
            } else {
                $return['errors']['main_error_message'] = 'Error occurred while saving the purchase contract.';
                $return['status'] = 'error';
            }

            return $this->response($return, $statusCode);
        } catch (\Throwable $ex) {
            return $this->error(['status' => "error", 'main_error_message' => $ex->getMessage()]);
        }
    }

    public function show_old(Request $request) {
        try {
            $statusCode = 422;
            $return = [];

            $id = $request->input('id');
            $purchase_contract = PurchaseContract::find($id);
            if ($purchase_contract) {
                $buyer = \App\Models\Buyer::where('id', $purchase_contract->buyer_id)->first();
                $purchase_contract->buyer = $buyer->name;
                $purchase_contract->total_qty = \App\Models\Purchase::where('contract_id', $purchase_contract->id)->sum('total_qty');
                $purchase_contract->total_amount = \App\Models\Purchase::where('contract_id', $purchase_contract->id)->sum('total_amount');
//                Get all Purchase Order Associate with this Contract
                $purchases = \App\Models\Purchase::where('contract_id', $purchase_contract->id)->get();
                foreach ($purchases as $purchase) {
//                    Purchase Order Budget
                    $budget = \App\Models\Budget::where('purchase_id', $purchase->id)->first();
                    $purchase->budget = $budget;
                    $techpack = \App\Models\Techpack::where('id', $purchase->techpack_id)->first();
                    $purchase->techpack = $techpack->title;

                    $purchase->qty = PurchaseItem::where('purchase_id', $purchase->id)->sum('qty');
                    $purchase->value = PurchaseItem::where('purchase_id', $purchase->id)->sum('total');

                    $purchase->purchase_items = PurchaseItem::where('purchase_id', $purchase->id)->get();

//                    Bookings Items For This Purchase
                    $booking_items = \App\Models\BookingItem::where('budget_id', $budget->id)->get();
                    foreach ($booking_items as $bookingItem) {
                        $booking = \App\Models\Booking::where('id', $bookingItem->booking_id)->first();
                        $bookingItem->booking_number = $booking->booking_number;
                        $supplier = \App\Models\Supplier::where('id', $booking->supplier_id)->first();
                        $bookingItem->supplier = $supplier->company_name;
                        $budget_item = \App\Models\BudgetItem::where('id', $bookingItem->budget_item_id)->first();
                        $item = \App\Models\Item::where('id', $budget_item->item_id)->first();
                        $bookingItem->item_name = $item->title;
                    }
                    $purchase->booking_items = $booking_items;
                    $purchase->total_booking = $booking_items->sum('total');
                }

//                Get All PI ASSOICATE WITH THIS CONTRACT
                $proformas = \App\Models\Proforma::where('purchase_contract_id', $purchase_contract->id)->where('status', 'BTB-Submitted')->get();
                foreach ($proformas as $pi) {
                    $supplier = \App\Models\Supplier::where('id', $pi->supplier_id)->first();
                    $pi->supplier = $supplier->company_name;

                    $user = \App\Models\User::where('id', $pi->user_id)->first();
                    $pi->placed_user = $user->full_name;

                    $pi_items = \App\Models\ProformaItem::where('proforma_id', $pi->id)->get();
                    foreach ($pi_items as $piItem) {
                        $item = \App\Models\Item::where('id', $piItem->item_id)->first();
                        $piItem->item_name = $item->title;
                    }
                    $pi->pi_items = $pi_items;
                }

//                Get All LC ASSOICATE WITH THIS CONTRACT
                $lcs = \App\Models\Lc::where('contract_id', $purchase_contract->id)->get();
                foreach ($lcs as $val) {
                    $supplier = \App\Models\Supplier::where('id', $val->supplier_id)->first();
                    $val->supplier = $supplier->company_name;
                    $val->supplier_city = $supplier->state;
                    $val->supplier_country = $supplier->country;

                    $bank = \App\Models\Bank::where('id', $val->bank)->first();
                    $val->bank_name = $bank->title;
                    $val->bank_branch = $bank->branch;
                    $val->bank_address = $bank->address;
                    $val->bank_country = $bank->country;

                    $proformaArray = explode(',', $val->proformas);
                    $proformaItems = \App\Models\Proforma::whereIn('id', $proformaArray)->get();
                    $val->proformas = $proformaItems;
                    $val->total_value = $proformaItems->sum('total');
                }

                $purchase_contract->lc_items = $lcs;
                $return['data'] = $purchase_contract;
                $return['purchases'] = $purchases;
                $return['proformas'] = $proformas;
                $statusCode = 200;
                $return['status'] = 'success';
            } else {
                $return['status'] = 'error';
            }
            return $this->response($return, $statusCode);
        } catch (\Throwable $ex) {
            return $this->error(['status' => "error", 'main_error_message' => $ex->getMessage(), 'AT LINE' => $ex->getLine()]);
        }
    }

    public function show(Request $request) {
        try {
            $statusCode = 422;
            $return = [];

            $id = $request->input('id');
            $purchase_contract = PurchaseContract::find($id);
            if ($purchase_contract) {
                $buyer = \App\Models\Buyer::find($purchase_contract->buyer_id); // Change to find()
                if ($buyer) { // Check if buyer is found
                    $purchase_contract->buyer = $buyer->name;
                } else {
                    // Handle the case when buyer is not found
                    throw new \Exception('Buyer not found for the purchase contract');
                }

                // Fetching total_qty and total_amount
                $purchase_contract->total_qty = \App\Models\Purchase::where('contract_id', $purchase_contract->id)->sum('total_qty');
                $purchase_contract->total_amount = \App\Models\Purchase::where('contract_id', $purchase_contract->id)->sum('total_amount');

                // Fetching all Purchase Order associated with this Contract
                $purchases = \App\Models\Purchase::where('contract_id', $purchase_contract->id)->get();

                foreach ($purchases as $purchase) {
                    // Fetching Purchase Order Budget
                    $budget = \App\Models\Budget::where('purchase_id', $purchase->id)->first();
                    if ($budget) { // Check if budget is found
                        $purchase->budget = $budget;
                        $techpack = \App\Models\Techpack::find($purchase->techpack_id); // Change to find()
                        if ($techpack) { // Check if techpack is found
                            $purchase->techpack = $techpack->title;
                        } else {
                            // Handle the case when techpack is not found
                            throw new \Exception('Techpack not found for the purchase');
                        }
                        // Fetching qty and value for the Purchase Items
                        $purchase->qty = PurchaseItem::where('purchase_id', $purchase->id)->sum('qty');
                        $purchase->value = PurchaseItem::where('purchase_id', $purchase->id)->sum('total');
                        // Fetching Purchase Items
                        $purchase->purchase_items = PurchaseItem::where('purchase_id', $purchase->id)->get();
                        // Fetching Booking Items For This Purchase
                        $booking_items = \App\Models\BookingItem::where('budget_id', $budget->id)->get();
                        foreach ($booking_items as $bookingItem) {
                            $booking = \App\Models\Booking::find($bookingItem->booking_id); // Change to find()
                            if ($booking) { // Check if booking is found
                                $bookingItem->booking_number = $booking->booking_number;
                                $supplier = \App\Models\Supplier::find($booking->supplier_id); // Change to find()
                                if ($supplier) { // Check if supplier is found
                                    $bookingItem->supplier = $supplier->company_name;
                                } else {
                                    // Handle the case when supplier is not found
                                    throw new \Exception('Supplier not found for the booking');
                                }
                                $budget_item = \App\Models\BudgetItem::find($bookingItem->budget_item_id); // Change to find()
                                if ($budget_item) { // Check if budget_item is found
                                    $item = \App\Models\Item::find($budget_item->item_id); // Change to find()
                                    if ($item) { // Check if item is found
                                        $bookingItem->item_name = $item->title;
                                    } else {
                                        // Handle the case when item is not found
                                        throw new \Exception('Item not found for the budget item');
                                    }
                                } else {
                                    // Handle the case when budget_item is not found
                                    throw new \Exception('Budget item not found for the booking');
                                }
                            } else {
                                // Handle the case when booking is not found
                                throw new \Exception('Booking not found for the booking item');
                            }
                        }
                        $purchase->booking_items = $booking_items;
                        $purchase->total_booking = $booking_items->sum('total');
                    } else {
                        // Handle the case when budget is not found
                        continue;
                    }
                }

                // Fetching All PI associated with this Contract
                $proformas = \App\Models\Proforma::where('purchase_contract_id', $purchase_contract->id)->where('status', 'BTB-Submitted')->get();
                foreach ($proformas as $pi) {
                    $supplier = \App\Models\Supplier::find($pi->supplier_id); // Change to find()
                    if ($supplier) { // Check if supplier is found
                        $pi->supplier = $supplier->company_name;
                    } else {
                        // Handle the case when supplier is not found
                        throw new \Exception('Supplier not found for the proforma');
                    }

                    $user = \App\Models\User::find($pi->user_id); // Change to find()
                    if ($user) { // Check if user is found
                        $pi->placed_user = $user->full_name;
                    } else {
                        // Handle the case when user is not found
                        throw new \Exception('User not found for the proforma');
                    }

                    $pi_items = \App\Models\ProformaItem::where('proforma_id', $pi->id)->get();
                    foreach ($pi_items as $piItem) {
                        $item = \App\Models\Item::find($piItem->item_id); // Change to find()
                        if ($item) { // Check if item is found
                            $piItem->item_name = $item->title;
                        } else {
                            // Handle the case when item is not found
                            throw new \Exception('Item not found for the proforma item');
                        }
                    }
                    $pi->pi_items = $pi_items;
                }

                // Fetching All LC associated with this Contract
                $lcs = \App\Models\Lc::where('contract_id', $purchase_contract->id)->get();
                foreach ($lcs as $val) {
                    $supplier = \App\Models\Supplier::find($val->supplier_id); // Change to find()
                    if ($supplier) { // Check if supplier is found
                        $val->supplier = $supplier->company_name;
                        $val->supplier_city = $supplier->state;
                        $val->supplier_country = $supplier->country;
                    } else {
                        // Handle the case when supplier is not found
                        throw new \Exception('Supplier not found for the LC');
                    }

                    $bank = \App\Models\Bank::find($val->bank); // Change to find()
                    if ($bank) { // Check if bank is found
                        $val->bank_name = $bank->title;
                        $val->bank_branch = $bank->branch;
                        $val->bank_address = $bank->address;
                        $val->bank_country = $bank->country;
                    } else {
                        // Handle the case when bank is not found
                        throw new \Exception('Bank not found for the LC');
                    }

                    $proformaArray = explode(',', $val->proformas);
                    $proformaItems = \App\Models\Proforma::whereIn('id', $proformaArray)->get();
                    $val->proformas = $proformaItems;
                    $val->total_value = $proformaItems->sum('total');
                }

                $purchase_contract->lc_items = $lcs;
                $return['data'] = $purchase_contract;
                $return['purchases'] = $purchases;
                $return['proformas'] = $proformas;
                $statusCode = 200;
                $return['status'] = 'success';
            } else {
                $return['status'] = 'error';
            }
            return $this->response($return, $statusCode);
        } catch (\Throwable $ex) {
            return $this->error(['status' => "error", 'main_error_message' => $ex->getMessage(), 'AT LINE' => $ex->getLine()]);
        }
    }

    public function update(Request $request) {
        try {
            $statusCode = 422;
            $return = [];
            $id = $request->input('id');

            $validatedData = $request->validate([
                'buyer_id' => 'required',
                'company_id' => 'required',
                'season' => 'required',
                'year' => 'required',
                'currency' => 'required',
                'title' => 'required',
                'pcc_avail' => 'nullable',
                'issued_date' => 'nullable',
                'shipment_date' => 'nullable',
                'expiry_date' => 'nullable',
            ]);

            $user_id = $request->user->id;

            $purchase_contract = PurchaseContract::findOrFail($id);

            // Update the purchase contract with validated data
            $purchase_contract->fill($validatedData);
            $purchase_contract->user_id = $user_id;

            if ($purchase_contract->save()) {
                // Construct the tag_number
                $buyer_id = $purchase_contract->buyer_id;
                $company_id = $purchase_contract->company_id;
                $season = $purchase_contract->season;
                $year = $purchase_contract->year;
                $serial_number = $purchase_contract->id;

                $purchase_contract->tag_number = $buyer_id . '/' . $company_id . '/' . $season . '/' . $year . '/' . $serial_number;

                // Save the updated purchase_contract
                if ($purchase_contract->save()) {
                    $return['data'] = $purchase_contract;
                    $statusCode = 200;
                    $return['status'] = 'success';
                } else {
                    $return['errors']['main_error_message'] = 'Error occurred while saving the purchase contract with updated tag number.';
                    $return['status'] = 'error';
                }
            } else {
                $return['errors']['main_error_message'] = 'Error occurred while saving the purchase contract.';
                $return['status'] = 'error';
            }

            return $this->response($return, $statusCode);
        } catch (\Throwable $ex) {
            return $this->error(['status' => "error", 'main_error_message' => $ex->getMessage()]);
        }
    }
}
