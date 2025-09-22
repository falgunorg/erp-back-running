<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Booking;
use Illuminate\Support\Facades\Validator;
use App\Models\BookingItem;
use App\Models\Team;
use App\Models\BudgetItem;
use App\Models\Budget;

class BookingController extends Controller {

    public function admin_index(Request $request) {
        try {
            $statusCode = 422;
            $return = [];
            $status = $request->input('status');
            $supplier_id = $request->input('supplier_id');
            $from_date = $request->input('from_date');
            $to_date = $request->input('to_date');
            $num_of_row = $request->input('num_of_row');

            // Query builder instance
            $query = Booking::orderBy('created_at', 'desc');
            // Apply filters
            if ($status) {
                $query->where('status', $status);
            }
            if ($supplier_id) {
                $query->where('supplier_id', $supplier_id);
            }
            if ($from_date && $to_date) {
                $query->whereBetween('booking_date', [$from_date, $to_date]);
            }
            $bookings = $query->take($num_of_row)->get();

            foreach ($bookings as $val) {
                $supplier = \App\Models\Supplier::where('id', $val->supplier_id)->first();
                $val->supplier = $supplier->company_name;

                $user = \App\Models\User::where('id', $val->user_id)->first();
                $val->user = $user->full_name;
            }
            $return['data'] = $bookings;
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
            $status = $request->input('status');
            $supplier_id = $request->input('supplier_id');
            $from_date = $request->input('from_date');
            $to_date = $request->input('to_date');
            $num_of_row = $request->input('num_of_row');
            $user_id = $request->user->id;
            $department = $request->input('department');
            $designation = $request->input('designation');
            $view = $request->input('view');
            $booking_user = $request->input('booking_user');

            //get all bookings   
            $all_bookings = Booking::all();
            // Query builder instance
            $query = Booking::orderBy('updated_at', 'desc');

            if ($department && $designation) {
                if ($department == "Merchandising" && $designation != "Deputy General Manager") {
                    if ($view) {
                        if ($view === 'self') {
                            $query->where('user_id', $user_id);
                        } else if ($view === 'team') {
                            $find_user_team = Team::whereRaw("FIND_IN_SET('$user_id', employees)")->first();
                            $team_users = explode(',', $find_user_team->employees);
                            $query->whereIn('user_id', $team_users);
                        }
                    }
                } else {
                    $query->orderBy('created_at', 'desc');
                }
            } else {
                $query->orderBy('created_at', 'desc');
            }


            // Apply filters
            if ($status) {
                $query->where('status', $status);
            }

            if ($booking_user) {
                $query->where('user_id', $booking_user);
            }

            if ($supplier_id) {
                $query->where('supplier_id', $supplier_id);
            }
            if ($from_date && $to_date) {
                $query->whereBetween('booking_date', [$from_date, $to_date]);
            }
            $bookings = $query->take($num_of_row)->get();

            foreach ($bookings as $val) {
                $supplier = \App\Models\Supplier::where('id', $val->supplier_id)->first();
                $val->supplier = $supplier->company_name;
                $user = \App\Models\User::where('id', $val->user_id)->first();
                $val->user = $user->full_name;
                $val->booking_items = BookingItem::where('booking_id', $val->id)->get();
            }
            $return['data'] = $bookings;
            $return['all_bookings'] = $all_bookings;
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
//           input_variable

            $validator = Validator::make($request->all(), [
                'supplier_id' => 'required',
                'currency' => 'required',
                'company_id' => 'required',
            ]);

            if ($validator->fails()) {
                return response()->json([
                            'errors' => $validator->errors()
                                ], 422);
            }

            $booking = new Booking([
                'supplier_id' => $request->input('supplier_id'),
                'currency' => $request->input('currency'),
                'booking_date' => $request->input('booking_date'),
                'delivery_date' => $request->input('delivery_date'),
                'status' => "Pending",
                'company_id' => $request->input('company_id'),
                'billing_address' => $request->input('billing_address'),
                'delivery_address' => $request->input('delivery_address'),
                'booking_from' => $request->input('booking_from'),
                'booking_to' => $request->input('booking_to'),
                'remark' => $request->input('remark'),
                'user_id' => $request->user->id,
            ]);

            $booking->save();
//            upload booking attachments
            if (request()->hasFile('attatchments')) {
                $files = request()->file('attatchments');
                foreach ($files as $file) {
                    $upload = new \App\Models\BookingFile();
                    $upload->booking_id = $booking->id;
                    $public_path = public_path();
                    $path = $public_path . '/' . "bookings";

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

//            add booking items 
            foreach ($request->booking_items as $index => $itemData) {
                $item = new BookingItem;
                $item->booking_id = $booking->id;
                $item->budget_id = $itemData['budget_id'];
                $item->budget_item_id = $itemData['budget_item_id'];
                $item->description = $itemData['description'];
                $item->remarks = $itemData['remarks'] ? $itemData['remarks'] : "";
                $item->unit = $itemData['unit'];
                $item->color = $itemData['color'] ? $itemData['color'] : "";
                $item->size = $itemData['size'] ? $itemData['size'] : "";
                $item->shade = $itemData['shade'] ? $itemData['shade'] : "";
                $item->tex = $itemData['tex'] ? $itemData['tex'] : "";
                $item->unit_price = $itemData['unit_price'];
                $item->qty = $itemData['qty'];
                $item->total = $itemData['total'];
                $item->user_id = $request->user->id;

                // Save booking item
                if ($item->save()) {
                    $budget_item = \App\Models\BudgetItem::where('id', $item->budget_item_id)->first();
                    $budget_item->used = $budget_item->used + $item->total;
                    $budget_item->save();
                }
                // Check if there's a photo for this item
                if ($request->hasFile("booking_items.{$index}.photo")) {
                    $photo = $request->file("booking_items.{$index}.photo");
                    $public_path = public_path();
                    $path = $public_path . '/' . "booking_items"; // Change to your desired directory
                    $pathinfo = pathinfo($photo->getClientOriginalName());
                    $basename = strtolower(str_replace(' ', '_', $pathinfo['filename']));
                    $extension = strtolower($pathinfo['extension']);
                    $file_name = $basename . '.' . $extension;
                    $finalpath = $path . '/' . $file_name;
                    if (file_exists($finalpath)) {
                        $file_name = $basename . time() . '.' . $extension;
                        $finalpath = $path . '/' . $file_name;
                    }
                    if ($photo->move($path, $file_name)) {
                        $item->photo = $file_name;
                        $item->save();
                    }
                }
            }

            // Update the total amount for the booking
            $total_amount = BookingItem::where('booking_id', $booking->id)->sum('total');
            $booking->total_amount = $total_amount;
            $booking->save();
            $statusCode = 200;
            $return['data'] = $booking;
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
            $booking = Booking::find($id);

            if ($booking) {
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

                $attachments = \App\Models\BookingFile::where('booking_id', $booking->id)->get();
                foreach ($attachments as $val) {
                    $val->file_source = url('') . '/bookings/' . $val->filename;
                }
                $booking->attachments = $attachments;
                $return['data'] = $booking;
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

    public function get_single_booking_item(Request $request) {
        try {
            $statusCode = 422;
            $return = [];
            $id = $request->input('id');
            $booking_item = BookingItem::find($id);
            if ($booking_item) {
                $budget = \App\Models\Budget::where('id', $booking_item->budget_id)->first();
                $purchase = \App\Models\Purchase::where('id', $budget->purchase_id)->first();
                $contract = \App\Models\PurchaseContract::where('id', $purchase->contract_id)->first();
                $booking_item->buyer_id = $contract->buyer_id;
                $booking = Booking::where('id', $booking_item->booking_id)->first();
                $booking_item->booking_user_id = $booking->user_id;
                $booking_item->supplier_id = $booking->supplier_id;
                $booking_item->company_id = $booking->company_id;
                $techpack = \App\Models\Techpack::where('id', $purchase->techpack_id)->first();
                $booking_item->techpack = $techpack->title;
                $booking_item->po_number = $purchase->po_number;
                $booking_item->budget = $budget->budget_number;
                $booking_item->techpack_id = $techpack->id;
                $budget_item = \App\Models\BudgetItem::where('id', $booking_item->budget_item_id)->first();
                $item = \App\Models\Item::where('id', $budget_item->item_id)->first();
                $booking_item->item_id = $item->id;
                $booking_item->item_name = $item->title;
                $booking_item->image_source = url('') . '/booking_items/' . $booking_item->photo;
                $store = \App\Models\Store::where('booking_item_id', $booking_item->id)->where('booking_id', $booking->id)->first();

                if ($store) {
                    $booking_item->already_received = \App\Models\Receive::where('store_id', $store->id)->sum('qty');
                    $booking_item->left_balance = $booking_item->qty - $booking_item->already_received;
                } else {
                    $booking_item->already_received = 0;
                    $booking_item->left_balance = $booking_item->qty;
                }
//                FOR ADD PI ITEMS

                $booking_item->already_added_pi = \App\Models\ProformaItem::where('booking_item_id', $booking_item->id)->sum('total');
                $booking_item->already_added_pi_qty = \App\Models\ProformaItem::where('booking_item_id', $booking_item->id)->sum('qty');
                $booking_item->left_pi_qty = $booking_item->qty - $booking_item->already_added_pi_qty;
                $booking_item->left_pi_total = $booking_item->left_pi_qty * $booking_item->unit_price;
                //                END ADD PI ITEMS              


                $return['data'] = $booking_item;
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

    public function get_booking_item_by_purchase_id(Request $request) {
        try {
            $statusCode = 422;
            $return = [];

            $purchase_id = $request->input('purchase_id');
            $booking_id = $request->input('booking_id');
            $booking = BookingItem::where('purchase_id', $purchase_id)->where('booking_id', $booking_id)->sum('total');

            if ($booking) {
                $return['data'] = $booking;
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
            $id = $request->input('booking_id');
            // Find the purchase by ID
            $booking = Booking::findOrFail($id);

            // Validate input
            $validator = Validator::make($request->all(), [
                'supplier_id' => 'required',
                'currency' => 'required',
                'company_id' => 'required',
            ]);

            if ($validator->fails()) {
                return response()->json(['errors' => $validator->errors()], 422);
            }
            // Update purchase attributes

            $booking->supplier_id = $request->input('supplier_id');
            $booking->currency = $request->input('currency');
            $booking->company_id = $request->input('company_id');
            $booking->booking_date = $request->input('booking_date') ? $request->input('booking_date') : "";
            $booking->delivery_date = $request->input('delivery_date') ? $request->input('delivery_date') : "";
            $booking->billing_address = $request->input('billing_address');
            $booking->delivery_address = $request->input('delivery_address');
            $booking->booking_from = $request->input('booking_from');
            $booking->booking_to = $request->input('booking_to');
            if ($request->input('remark')) {
                $booking->remark = $request->input('remark');
            }

            foreach ($request->booking_items as $index => $itemData) {
                if (isset($itemData['id'])) {
                    // Update existing item
                    $item = BookingItem::findOrFail($itemData['id']);
                    // Update the item's properties here as needed
                } else {
                    // Create a new item
                    $item = new BookingItem;
                    // Set the item's properties based on $itemData as you did before
                }

                $item->booking_id = $booking->id;
                $item->budget_id = $itemData['budget_id'];
                $item->budget_item_id = $itemData['budget_item_id'];
                $item->description = $itemData['description'];
                $item->remarks = $itemData['remarks'] ? $itemData['remarks'] : "";
                $item->unit = $itemData['unit'];
                $item->color = $itemData['color'] ? $itemData['color'] : "";
                $item->size = $itemData['size'] ? $itemData['size'] : "";
                $item->shade = $itemData['shade'] ? $itemData['shade'] : "";
                $item->tex = $itemData['tex'] ? $itemData['tex'] : "";
                $item->unit_price = $itemData['unit_price'];
                $item->qty = $itemData['qty'];
                $item->total = $itemData['total'];
                $item->user_id = $request->user->id;

                // Save or update the booking item
                $item->save();
                // Check if there's a photo for this item
                if ($request->hasFile("booking_items.{$index}.photo")) {
                    $photo = $request->file("booking_items.{$index}.photo");
                    $public_path = public_path();
                    $path = $public_path . '/' . "booking_items"; // Change to your desired directory
                    $pathinfo = pathinfo($photo->getClientOriginalName());
                    $basename = strtolower(str_replace(' ', '_', $pathinfo['filename']));
                    $extension = strtolower($pathinfo['extension']);
                    $file_name = $basename . '.' . $extension;
                    $finalpath = $path . '/' . $file_name;
                    if (file_exists($finalpath)) {
                        $file_name = $basename . time() . '.' . $extension;
                        $finalpath = $path . '/' . $file_name;
                    }
                    if ($photo->move($path, $file_name)) {
                        $item->photo = $file_name;
                        $item->save();
                    }
                }
            }

            $total_amount = BookingItem::where('booking_id', $id)->sum('total');
            $booking->total_amount = $total_amount;

            if (request()->hasFile('attatchments')) {
                $files = request()->file('attatchments');
                foreach ($files as $file) {
                    $upload = new \App\Models\BookingFile();
                    $upload->booking_id = $booking->id;
                    $public_path = public_path();
                    $path = $public_path . '/' . "bookings";

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
            $booking->save();
            $statusCode = 200;
            $return['data'] = $booking;
            $return['status'] = 'success';

            return $this->response($return, $statusCode);
        } catch (\Throwable $ex) {
            return $this->error(['status' => 'error', 'main_error_message' => $ex->getMessage()]);
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

            $booking = Booking::find($id);
            if (!$booking) {
                return response()->json([
                            'status' => 'error',
                            'main_error_message' => 'Budget not found'
                                ], 404);
            }
            if ($status == "Placed") {
                $booking->placed_by = $user_id;
                $booking->placed_at = date('Y-m-d H:i:s');

                $find_user_team = Team::whereRaw("FIND_IN_SET('$user_id', employees)")->first();
                $team_leader = $find_user_team->team_lead;
                $notification = new \App\Models\Notification;
                $notification->title = "Booking Placed By " . $user->full_name;
                $notification->receiver = $team_leader;
                $notification->url = "/merchandising/bookings-details/" . $booking->id;
                $notification->description = "Please Take Necessary Action";
                $notification->is_read = 0;
                $notification->save();
            } else if ($status == "Confirmed") {
                $booking->confirmed_by = $user_id;
                $booking->confirmed_at = date('Y-m-d H:i:s');

                $notification = new \App\Models\Notification;
                $notification->title = "Booking Confirmed By " . $user->full_name;
                $notification->receiver = $booking->user_id;
                $notification->url = "/merchandising/bookings-details/" . $booking->id;
                $notification->description = "Please Take Necessary Action";
                $notification->is_read = 0;
                $notification->save();
            } else if ($status == "Rejected") {
                $booking->placed_by = 0;
                $booking->confirmed_by = 0;
                $booking->rejected_by = $user_id;
                $booking->rejected_at = date('Y-m-d H:i:s');
                $booking->placed_at = '';
                $booking->confirmed_at = '';

                $notification = new \App\Models\Notification;
                $notification->title = "Booking Rejected By " . $user->full_name;
                $notification->receiver = $booking->user_id;
                $notification->url = "/merchandising/bookings-details/" . $booking->id;
                $notification->description = "Please Take Necessary Action";
                $notification->is_read = 0;
                $notification->save();
            }

            $booking->status = $status;
            $booking->save();
            $statusCode = 200;
            $return['data'] = $booking;
            $return['status'] = 'success';
            return $this->response($return, $statusCode);
        } catch (\Throwable $ex) {
            return $this->error(['status' => "error", 'main_error_message' => $ex->getMessage()]);
        }
    }

    public function bookings_overview(Request $request) {
        try {
            $statusCode = 422;
            $return = [];
            $user_id = $request->user->id;
            $from_date = $request->input('from_date');
            $to_date = $request->input('to_date');
            $status = $request->input('status');
            $purchase_id = $request->input('purchase_id');
            $num_of_row = $request->input('num_of_row');
            $filter_items = $request->input('filter_items');
            $view = $request->input('view');
            $department = $request->input('department');
            $designation = $request->input('designation');
            $query = Budget::orderBy('updated_at', 'desc');
            if ($department && $designation) {
                if ($department == "Merchandising" && $designation != "Deputy General Manager") {
                    if ($view) {
                        if ($view === 'self') {
                            $query->where('user_id', $user_id);
                        } else if ($view === 'team') {
                            $find_user_team = Team::whereRaw("FIND_IN_SET('$user_id', employees)")->first();
                            $team_users = explode(',', $find_user_team->employees);
                            $query->whereIn('user_id', $team_users);
                        }
                    }
                } else {
                    $query->orderBy('created_at', 'desc');
                }
            } else {
                $query->orderBy('created_at', 'desc');
            }

            if ($status) {
                $query->where('status', $status);
            }

            if ($purchase_id) {
                $query->where('purchase_id', $purchase_id);
            }
            if ($filter_items) {
                $query->whereIn('id', $filter_items);
            }
            // Apply date range filter if both "from_date" and "to_date" are provided
            if ($from_date && $to_date) {
                $query->whereBetween('created_at', [$from_date, $to_date]);
            }
            $budgets = $query->take($num_of_row)->get();
            foreach ($budgets as $val) {
                $budget_items = BudgetItem::where('budget_id', $val->id)->get();
                $purchase = \App\Models\Purchase::where('id', $val->purchase_id)->first();
                $val->po_number = $purchase->po_number;
                $techpack = \App\Models\Techpack::where('id', $purchase->techpack_id)->first();
                $val->techpack = $techpack->title;
                $user = \App\Models\User::where('id', $val->user_id)->first();
                $val->user = $user->full_name;
                foreach ($budget_items as $item) {
                    $budget_item = \App\Models\Item::where('id', $item->item_id)->first();
                    $item->item_name = $budget_item->title;
                    $item->booking_qty = \App\Models\BookingItem::where('budget_item_id', $item->id)->sum('qty');
                    $item->left_booking = $item->total_req_qty - $item->booking_qty;
                    $item->received_qty = \App\Models\Receive::where('budget_item_id', $item->id)->sum('qty');
                    $item->store_balance = \App\Models\Store::where('budget_item_id', $item->id)->sum('qty');

                    $supplier = \App\Models\Supplier::where('id', $item->supplier_id)->first();
                    $item->supplier = $supplier->company_name;

                    $item->used_qty = $item->received_qty - $item->store_balance;
                    $item->left_received_qty = $item->booking_qty - $item->received_qty;
                    if ($item->total_req_qty == $item->booking_qty) {
                        $item->booking_status = "Booked";
                    } else if ($item->booking_qty !== 0) {
                        $item->booking_status = "Partial";
                    } else {
                        $item->booking_status = "Pending";
                    }
                }

                $val->budget_items = $budget_items;
            }

            $return['data'] = $budgets;
            $statusCode = 200;
            $return['status'] = 'success';

            return $this->response($return, $statusCode);
        } catch (\Throwable $ex) {
            return $this->error(['status' => "error", 'main_error_message' => $ex->getMessage()]);
        }
    }

    public function get_booking_items_by_supplier_id_without_included_pi(Request $request) {
        try {
            $statusCode = 422;
            $return = [];
            $supplier_id = $request->input('supplier_id');
            $booking_user = $request->input('booking_user');

            // Retrieve confirmed bookings for the specified supplier
            $bookings = Booking::where('supplier_id', $supplier_id)
                            ->where('user_id', $booking_user)
                            ->where('status', 'Confirmed')->get();

            $bookingItems = [];

            foreach ($bookings as $booking) {
                // Retrieve booking items for each booking
                $booking_items = BookingItem::where('booking_id', $booking->id)->get();

                foreach ($booking_items as $val) {
                    // Check if there is an associated proforma item
                    $exist_proforma_item = \App\Models\ProformaItem::where('booking_id', $val->booking_id)
                                    ->where('booking_item_id', $val->id)->first();

                    // If there is no associated proforma item, add the booking item
                    if (!$exist_proforma_item) {
                        // Fetch additional data for $val
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
                        // Set exist_proforma flag to 0
                        $val->exist_proforma = 0;
                        //addition for auto PI
                        $val->booking_qty = $val->qty;
                        $val->booking_unit_price = $val->unit_price;

                        // Merge the booking item into the main array
                        $bookingItems[] = $val->toArray();
                    }
                }
            }

            if (!empty($bookingItems)) {
                $return['data'] = $bookingItems;
                $statusCode = 200;
                $return['status'] = 'success';
            } else {
                $return['data'] = [];
                $statusCode = 200;
                $return['status'] = 'success';
            }
            return $this->response($return, $statusCode);
        } catch (\Throwable $ex) {
            return $this->error(['status' => "error", 'main_error_message' => $ex->getMessage()]);
        }
    }

    //THIS IS FOR AUTOMATIC PI SUBMISSION
    public function get_booking_items_without_included_pi(Request $request) {
        try {
            $statusCode = 422;
            $return = [];

            $booking_user = $request->input('booking_user');
            // Retrieve confirmed bookings for the specified supplier
            $bookings = Booking::where('user_id', $booking_user)
                            ->where('status', 'Confirmed')
                            ->orderBy('created_at', 'desc')->get();
            foreach ($bookings as $booking) {
                $booking_user = \App\Models\User::where('id', $booking->user_id)->first();
                $booking->user = $booking_user->full_name;
                $supplier = \App\Models\Supplier::where('id', $booking->supplier_id)->first();
                $booking->supplier_name = $supplier->company_name;
                $booking_items = BookingItem::where('booking_id', $booking->id)->get();
                foreach ($booking_items as $val) {
                    $exist_proforma_item = \App\Models\ProformaItem::where('booking_id', $val->booking_id)
                                    ->where('booking_item_id', $val->id)->first();
                    if (!$exist_proforma_item) {
                        $val->exist_proforma = 0;
                    } else {
                        $val->exist_proforma = 1;
                    }
                    // Fetch additional data for $val
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
            }
            if (!empty($bookings)) {
                $return['data'] = $bookings;
                $statusCode = 200;
                $return['status'] = 'success';
            } else {
                $return['data'] = [];
                $statusCode = 200;
                $return['status'] = 'success';
            }
            return $this->response($return, $statusCode);
        } catch (\Throwable $ex) {
            return $this->error(['status' => "error", 'main_error_message' => $ex->getMessage(), 'AT LINE' => $ex->getLine()]);
        }
    }
}
