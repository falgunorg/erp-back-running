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

class MerchandisingPowerController extends Controller {

    public function index(Request $request) {
        try {
            $statusCode = 422;
            $return = [];

            $techpacks = \App\Models\Techpack::count();
            $sors = \App\Models\Sor::count();
            $costings = \App\Models\Costing::count();
            $purchases = \App\Models\Purchase::count();
            $budgets = \App\Models\Budget::count();
            $bookings = \App\Models\Booking::count();
            $proformas = \App\Models\Proforma::count();
            $contracts = \App\Models\PurchaseContract::count();

            // Create an empty object to store the report data
            $report = new \stdClass();
            $report->techpacks = $techpacks;
            $report->sors = $sors;
            $report->costings = $costings;
            $report->purchases = $purchases;
            $report->budgets = $budgets;
            $report->bookings = $bookings;
            $report->proformas = $proformas;
            $report->contracts = $contracts;

            $return['data'] = $report;
            $statusCode = 200;

            return response($return, $statusCode);
        } catch (\Throwable $ex) {
            return response(['status' => 'error', 'main_error_message' => $ex->getMessage()], 500);
        }
    }

    public function contracts(Request $request) {
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
            $status = $request->input('status');
            $user_id = $request->input('user_id');
            $search = $request->input('search');
            $buyer = $request->input('buyer_id');
            $contract_id = $request->input('contract_id');

            // Base query
            $query = \App\Models\PurchaseContract::query();
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
                $query->whereYear('created_at', $currentYear);
            }


            if ($contract_id) {
                $contract = \App\Models\PurchaseContract::where('id', $contract_id)->first();
                $query->where('buyer_id', $contract->buyer_id);
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
            if ($buyer) {
                $query->where('buyer_id', $buyer);
            }

            // Final ordering and pagination
            $contracts = $query->orderBy('created_at', 'desc')->paginate(100);
            foreach ($contracts as $contract) {
                $buyer = \App\Models\Buyer::find($contract->buyer_id); // Change to find()
                if ($buyer) { // Check if buyer is found
                    $contract->buyer = $buyer->name;
                } else {
                    // Handle the case when buyer is not found
                    throw new \Exception('Buyer not found for the purchase contract');
                }


                $company = \App\Models\Company::where('id', $contract->company_id)->first();

                if ($company) {
                    $contract->company_title = $company->title;
                } else {
                    $contract->company_title = "N/A";
                }

                // Fetching total_qty and total_amount
                $contract->total_qty = \App\Models\Purchase::where('contract_id', $contract->id)->sum('total_qty');
                $contract->total_amount = \App\Models\Purchase::where('contract_id', $contract->id)->sum('total_amount');

                // Fetching all Purchase Order associated with this Contract
                $purchases = \App\Models\Purchase::where('contract_id', $contract->id)->get();

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
                $contract->purchases = $purchases;
                // Fetching All PI associated with this Contract
                $proformas = \App\Models\Proforma::where('purchase_contract_id', $contract->id)->where('status', 'BTB-Submitted')->get();
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
                $contract->proformas = $proformas;

                // Fetching All LC associated with this Contract
                $lcs = \App\Models\Lc::where('contract_id', $contract->id)->get();
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
                $contract->lc_items = $lcs;
            }

            $return['contracts'] = $contracts;
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

    public function techpacks(Request $request) {
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
            $buyer = $request->input('buyer_id');
            $contract_id = $request->input('contract_id');

            // Base query
            $query = \App\Models\Techpack::query();
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


            if ($contract_id) {
                $contract = \App\Models\PurchaseContract::where('id', $contract_id)->first();
                $query->where('buyer_id', $contract->buyer_id);
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
            if ($buyer) {
                $query->where('buyer_id', $buyer);
            }

            // Final ordering and pagination
            $techpacks = $query->orderBy('created_at', 'desc')->paginate(100);

            foreach ($techpacks as $techpack) {
                $buyer = \App\Models\Buyer::where('id', $techpack->buyer_id)->first();
                $techpack->buyer = $buyer->name;
                $attatchments = \App\Models\TechpackFile::where('techpack_id', $techpack->id)->get();
                foreach ($attatchments as $item) {
                    $item->file_source = url('') . '/techpacks/' . $item->filename;
                }
                $techpack->file_source = url('') . '/techpacks/' . $techpack->photo;
                $techpack_user = \App\Models\User::where('id', $techpack->user_id)->first();
                $techpack->techpack_by = $techpack_user->full_name;
                $techpack->attatchments = $attatchments;

                $consumption = \App\Models\Consumption::where('techpack_id', $techpack->id)->first();
                if ($consumption) {
                    $techpack->consumption_number = $consumption->consumption_number;
                    $consumption_user = \App\Models\User::where('id', $consumption->user_id)->first();
                    $techpack->consumption_by = $consumption_user->full_name;
                    $consumption_items = \App\Models\ConsumptionItem::where('consumption_id', $consumption->id)->get();

                    foreach ($consumption_items as $val) {
                        $item = \App\Models\Item::where('id', $val->item_id)->first();
                        $val->item_name = $item->title;
                        $val->actual = $val->qty;
                    }
                    $techpack->consumption_items = $consumption_items;
                    $return['consumption_items'] = $consumption_items;
                } else {
                    $techpack->consumption_number = "N/A";
                    $techpack->consumption_by = "N/A";
                    $techpack->consumption_items = [];
                    $return['consumption_items'] = [];
                }
            }

            $return['techpacks'] = $techpacks;
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

    public function sors(Request $request) {
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
            $buyer = $request->input('buyer_id');
            $contract_id = $request->input('contract_id');

            // Base query
            $query = \App\Models\Sor::query();
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


            if ($contract_id) {
                $contract = \App\Models\PurchaseContract::where('id', $contract_id)->first();
                $query->where('buyer_id', $contract->buyer_id);
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
            if ($buyer) {
                $query->where('buyer_id', $buyer);
            }

            // Final ordering and pagination
            $sors = $query->orderBy('created_at', 'desc')->paginate(100);

            foreach ($sors as $sor) {
                $sor_items = SorItem::where('sor_id', $sor->id)->get();

                foreach ($sor_items as $val) {
                    $item = SampleStore::where('id', $val->sample_store_id)->first();
                    $val->title = $item->title;
                    $val->store_number = $item->store_number;
                    $balance = SampleBalance::where('sample_store_id', $val->sample_store_id)->first();
                }
                $sor->sor_items = $sor_items;

                $colorArray = explode(',', $sor->colors);
                $colors = \App\Models\Color::whereIn('id', $colorArray)->get();
                $sor->colorList = $colors;

                $sizeArray = explode(',', $sor->sizes);
                $sizes = \App\Models\Size::whereIn('id', $sizeArray)->get();
                $sor->sizeList = $sizes;

                $buyer = \App\Models\Buyer::where('id', $sor->buyer_id)->first();
                $user = \App\Models\User::where('id', $sor->user_id)->first();
                $techpack = \App\Models\Techpack::where('id', $sor->techpack_id)->first();
                $sor->techpack = $techpack->title;
                $sor->buyer = $buyer->name;
                $sor->user = $user->full_name;
                $sample_type = \App\Models\SampleType::where('id', $sor->sample_type)->first();
                $sor->sample_type_name = $sample_type->title;
                $sor->image_source = url('') . '/sors/' . $sor->photo;

                $attachments = \App\Models\SorFile::where('sor_id', $sor->id)->orderBy('created_at', 'desc')->get();
                foreach ($attachments as $val) {
                    $val->file_source = url('') . '/sors/' . $val->filename;
                }

                $sor->attachments = $attachments;
            }

            $return['techpacks'] = $techpacks;
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

    public function costings(Request $request) {
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
            $buyer = $request->input('buyer_id');
            $contract_id = $request->input('contract_id');

            // Base query
            $query = \App\Models\Costing::query();
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


            if ($contract_id) {
                $contract = \App\Models\PurchaseContract::where('id', $contract_id)->first();
                $query->where('buyer_id', $contract->buyer_id);
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
            if ($buyer) {
                $query->where('buyer_id', $buyer);
            }

            // Final ordering and pagination
            $costings = $query->orderBy('created_at', 'desc')->paginate(100);
            foreach ($costings as $costing) {
                //retrive the user
                $user = \App\Models\User::where('id', $costing->user_id)->first();
                $costing->user = $user->full_name;
                //  reteive the techpack
                $techpack = \App\Models\Techpack::where('id', $costing->techpack_id)->first();
                $costing->techpack_number = $techpack->title;
                $costing->season = $techpack->season;
                $costing->file_source = url('') . '/techpacks/' . $techpack->photo;

                // retrive the consumption
                $consumption = \App\Models\Consumption::where('techpack_id', $techpack->id)->first();
                $costing->consumption_number = $consumption->consumption_number;
                $costing->consumption_id = $consumption->id;
                // retrive the buyer (techpack wise) 
                $buyer = \App\Models\Buyer::where('id', $techpack->buyer_id)->first();
                $costing->buyer = $buyer->name;

                $costing_items = CostingItem::where('costing_id', $costing->id)->get();

                foreach ($costing_items as $val) {
                    $item = \App\Models\Item::where('id', $val->item_id)->first();
                    $val->item_name = $item->title;
                }

                $costing->costing_items = $costing_items;
            }

            $return['costings'] = $costings;
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

    public function purchases(Request $request) {
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
            $buyer = $request->input('buyer_id');
            $contract_id = $request->input('contract_id');

            // Base query
            $query = \App\Models\Purchase::query();
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


            if ($contract_id) {
                $contract = \App\Models\PurchaseContract::where('id', $contract_id)->first();
                $query->where('buyer_id', $contract->buyer_id);
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
            if ($buyer) {
                $query->where('buyer_id', $buyer);
            }

            // Final ordering and pagination
            $purchases = $query->orderBy('created_at', 'desc')->paginate(100);
            foreach ($purchases as $purchase) {
                $contract = \App\Models\PurchaseContract::where('id', $purchase->contract_id)->first();
                $purchase->purchase_contarct = $contract->title;

                $purchase->season = $contract->season;
                $purchase->currency = $contract->currency;

                $buyer = \App\Models\Buyer::where('id', $contract->buyer_id)->first();
                $purchase->buyer = $buyer->name;
                $vendor = \App\Models\Company::where('id', $contract->company_id)->first();
                $purchase->vendor = $vendor->title;
                $purchase_items = PurchaseItem::where('purchase_id', $purchase->id)->get();
                $purchase->purchase_items = $purchase_items;
                $attachments = \App\Models\PurchaseFile::where('purchase_id', $purchase->id)->get();
                foreach ($attachments as $val) {
                    $val->file_source = url('') . '/purchases/' . $val->filename;
                }
                $purchase->attachments = $attachments;
                $techpack = \App\Models\Techpack::where('id', $purchase->techpack_id)->first();
                $purchase->techpack = $techpack->title;

                $costing = \App\Models\Costing::where('techpack_id', $techpack->id)->first();
                $costing_items = \App\Models\CostingItem::where('costing_id', $costing->id)->get();
                if ($costing_items) {
                    foreach ($costing_items as $val) {
                        $val->cuttable_width = "";
                        $val->description = $val->description ? $val->description : "";
                    }
                    $purchase->costing_items = $costing_items;
                } else {
                    $purchase->costing_items = [];
                }
                $colorArray = explode(',', $purchase->colors);
                $colors = \App\Models\Color::whereIn('id', $colorArray)->get();
                $purchase->colorsList = $colors;

                $sizeArray = explode(',', $purchase->sizes);
                $sizes = \App\Models\Size::whereIn('id', $sizeArray)->get();
                $purchase->sizesList = $sizes;
            }

            $return['purchases'] = $purchases;
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

    public function budgets(Request $request) {
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
            $buyer = $request->input('buyer_id');
            $contract_id = $request->input('contract_id');

            // Base query
            $query = \App\Models\Budget::query();
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


            if ($contract_id) {
                $contract = \App\Models\PurchaseContract::where('id', $contract_id)->first();
                $query->where('buyer_id', $contract->buyer_id);
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
            if ($buyer) {
                $query->where('buyer_id', $buyer);
            }

            // Final ordering and pagination
            $budgets = $query->orderBy('created_at', 'desc')->paginate(100);
            foreach ($budgets as $budget) {
                //retrive the user
                $user = \App\Models\User::where('id', $budget->user_id)->first();
                $budget->user = $user->full_name;

                $purchase = \App\Models\Purchase::where('id', $budget->purchase_id)->first();
                $budget->po_number = $purchase->po_number;

                $techpack = \App\Models\Techpack::where('id', $purchase->techpack_id)->first();
                $budget->techpack = $techpack->title;

                $budget->file_source = url('') . '/techpacks/' . $techpack->photo;

                //  reteive the purchase contract
                $contract = \App\Models\PurchaseContract::where('id', $purchase->contract_id)->first();
                $budget->contract_number = $contract->title;

                //    retrive contract company from our side
                $company = \App\Models\Company::where('id', $contract->company_id)->first();
                $budget->company = $company->title;

//         retrive currency 
                $budget->currency = $contract->currency;
                $budget->season = $contract->season;

                // retrive the buyer (contract wise) 
                $buyer = \App\Models\Buyer::where('id', $contract->buyer_id)->first();
                $budget->buyer = $buyer->name;

                //   retrive colors from a budget 
                $colorArray = explode(',', $budget->colors);
                $colors = \App\Models\Color::whereIn('id', $colorArray)->get();
                $budget->colorsList = $colors;

                //retrive sizes from budget
                $sizeArray = explode(',', $budget->sizes);
                $sizes = \App\Models\Size::whereIn('id', $sizeArray)->get();
                $budget->sizesList = $sizes;

                $budget_items = BudgetItem::where('budget_id', $budget->id)->get();

                foreach ($budget_items as $val) {
                    $supplier = \App\Models\Supplier::where('id', $val->supplier_id)->first();
                    $val->supplier = $supplier->company_name . ', ' . $supplier->country;
                    $item = \App\Models\Item::where('id', $val->item_id)->first();
                    $val->item_name = $item->title;
                }
                $budget->budget_items = $budget_items;

                $budget->total_budget_used = number_format($budget_items->sum('used_budget'), 2);
                $budget->order_total_cost = number_format($budget_items->sum('order_total_cost'), 2);
                $budget->total_unit_cost = number_format($budget_items->sum('unit_total_cost'), 2);
                $budget->balance = number_format($budget->total_order_value - $budget->total_cost, 2);

                $attachments = \App\Models\BudgetFile::where('budget_id', $budget->id)->get();
                foreach ($attachments as $val) {
                    $val->file_source = url('') . '/budgets/' . $val->filename;
                }
                $budget->attachments = $attachments;

                // Signatures 
//                mr
                $placed_by = \App\Models\User::where('id', $budget->placed_by)->first();
                if ($placed_by) {
                    $budget->placed_by_sign = url('') . '/signs/' . $placed_by->sign;
                }

//                mr team lead
                $confirmed_by = \App\Models\User::where('id', $budget->confirmed_by)->first();
                if ($confirmed_by) {
                    $budget->confirmed_by_sign = url('') . '/signs/' . $confirmed_by->sign;
                }



//                mr head
                $submitted_by = \App\Models\User::where('id', $budget->submitted_by)->first();
                if ($submitted_by) {
                    $budget->submitted_by_sign = url('') . '/signs/' . $submitted_by->sign;
                }

//                Audit

                $checked_by = \App\Models\User::where('id', $budget->checked_by)->first();
                if ($checked_by) {
                    $budget->checked_by_sign = url('') . '/signs/' . $checked_by->sign;
                }


                //          Finance
                $cost_approved_by = \App\Models\User::where('id', $budget->cost_approved_by)->first();
                if ($cost_approved_by) {
                    $budget->cost_approved_by_sign = url('') . '/signs/' . $cost_approved_by->sign;
                }

                //          GM
                $finalized_by = \App\Models\User::where('id', $budget->finalized_by)->first();
                if ($finalized_by) {
                    $budget->finalized_by_sign = url('') . '/signs/' . $finalized_by->sign;
                }


                //     MD
                $approved_by = \App\Models\User::where('id', $budget->approved_by)->first();
                if ($approved_by) {
                    $budget->approved_by_sign = url('') . '/signs/' . $approved_by->sign;
                }
            }

            $return['budgets'] = $budgets;
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

    public function bookings(Request $request) {
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
            $buyer = $request->input('buyer_id');
            $contract_id = $request->input('contract_id');

            // Base query
            $query = \App\Models\Booking::query();
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


            if ($contract_id) {
                $contract = \App\Models\PurchaseContract::where('id', $contract_id)->first();
                $query->where('buyer_id', $contract->buyer_id);
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
            if ($buyer) {
                $query->where('buyer_id', $buyer);
            }

            // Final ordering and pagination
            $bookings = $query->orderBy('created_at', 'desc')->paginate(100);
            foreach ($bookings as $booking) {

                $supplier = \App\Models\Supplier::where('id', $booking->supplier_id)->first();
                $booking->supplier = $supplier->company_name;
                $company = \App\Models\Company::where('id', $booking->company_id)->first();
                $booking->company = $company->title;

                // Signatures 
//                mr
                $placed_by = \App\Models\User::where('id', $booking->placed_by)->first();
                if ($placed_by) {
                    $booking->placed_by_sign = url('') . '/signs/' . $placed_by->sign;
                }

//                mr team lead
                $confirmed_by = \App\Models\User::where('id', $booking->confirmed_by)->first();
                if ($confirmed_by) {
                    $booking->confirmed_by_sign = url('') . '/signs/' . $confirmed_by->sign;
                }
                $booking_items = BookingItem::where('booking_id', $booking->id)->get();
                foreach ($booking_items as $val) {
                    $budget = \App\Models\Budget::where('id', $val->budget_id)->first();
                    $val->budget_number = $budget->budget_number;
                    $purchase = \App\Models\Purchase::where('id', $budget->purchase_id)->first();
                    $contract = \App\Models\PurchaseContract::where('id', $purchase->contract_id)->first();
                    $buyer = \App\Models\Buyer::where('id', $contract->buyer_id)->first();
                    $val->buyer = $buyer->name;
                    $val->booking_number = $booking->booking_number;
                    $techpack = \App\Models\Techpack::where('id', $purchase->techpack_id)->first();
                    $val->techpack = $techpack->title;
                    $val->techpack_id = $techpack->id;
                    $val->po_number = $purchase->po_number;
                    $budget_item = \App\Models\BudgetItem::where('id', $val->budget_item_id)->first();
                    $item = \App\Models\Item::where('id', $budget_item->item_id)->first();
                    $val->item_id = $item->id;
                    $val->item_name = $item->title;
                    $val->image_source = url('') . '/booking_items/' . $val->photo;
                    $store = \App\Models\Receive::where('booking_item_id', $val->id)->sum('qty');
                    $val->already_received = $store;
                    $val->left_balance = $val->qty - $store;
                }
                $booking->booking_items = $booking_items;
//ghfjghfj
                $attachments = \App\Models\BookingFile::where('booking_id', $booking->id)->get();
                foreach ($attachments as $val) {
                    $val->file_source = url('') . '/bookings/' . $val->filename;
                }
                $booking->attachments = $attachments;
            }

            $return['bookings'] = $bookings;
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

    public function proformas(Request $request) {
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
            $buyer = $request->input('buyer_id');
            $contract_id = $request->input('contract_id');

            // Base query
            $query = \App\Models\Proforma::query();
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


            if ($contract_id) {
                $contract = \App\Models\PurchaseContract::where('id', $contract_id)->first();
                $query->where('buyer_id', $contract->buyer_id);
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
            if ($buyer) {
                $query->where('buyer_id', $buyer);
            }

            // Final ordering and pagination
            $proformas = $query->orderBy('created_at', 'desc')->paginate(100);
            foreach ($proformas as $proforma) {
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
            }

            $return['proformas'] = $proformas;
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

}
