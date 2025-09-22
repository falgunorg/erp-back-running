<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Purchase;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\DB;
use App\Models\PurchaseItem;
use App\Models\Team;

class PurchaseController extends Controller {

    public function admin_index(Request $request) {
        try {
            $statusCode = 422;
            $return = [];
            $buyer = $request->input('buyer');
            $vendor = $request->input('vendor');
            $from_date = $request->input('from_date');
            $to_date = $request->input('to_date');
            $status = $request->input('status');
            $num_of_row = $request->input('num_of_row');

            // Query builder instance
            $query = Purchase::orderBy('created_at', 'desc');

            // Apply filters
            if ($status) {
                $query->where('status', $status);
            }
            if ($from_date && $to_date) {
                $query->whereBetween('order_date', [$from_date, $to_date]);
            }

            if ($buyer) {
                $query->where('buyer_id', $buyer);
            }

            if ($vendor) {
                $query->where('vendor_id', $vendor);
            }

            $purchases = $query->take($num_of_row)->get();
            foreach ($purchases as $val) {
                $buyer = \App\Models\Buyer::where('id', $val->buyer_id)->first();
                $val->buyer = $buyer->name;
                $vendor = \App\Models\Company::where('id', $val->vendor_id)->first();
                $val->vendor = $vendor->title;
                $techpack = \App\Models\Techpack::where('id', $val->techpack_id)->first();
                $val->techpack = $techpack->title;
            }
            $return['data'] = $purchases;
            $return['status'] = 'success';
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
            $buyer = $request->input('buyer');
            $vendor = $request->input('vendor');
            $from_date = $request->input('from_date');
            $to_date = $request->input('to_date');
            $status = $request->input('status');
            $num_of_row = $request->input('num_of_row');
            $view = $request->input('view');
            $department = $request->input('department');
            $designation = $request->input('designation');
            $user_id = $request->user->id;
//         all items 
            $all_purchase = Purchase::all();
            // Query builder instance
            $query = Purchase::orderBy('created_at', 'desc');
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
            if ($from_date && $to_date) {
                $query->whereBetween('order_date', [$from_date, $to_date]);
            }

            if ($buyer) {
                $query->where('buyer_id', $buyer);
            }

            if ($vendor) {
                $query->where('vendor_id', $vendor);
            }

            $purchases = $query->take($num_of_row)->get();

            foreach ($purchases as $val) {
                $contract = \App\Models\PurchaseContract::where('id', $val->contract_id)->first();
                $val->contract_number = $contract->contract_number;
                $val->season = $contract->season;
                $val->currency = $contract->currency;
                $buyer = \App\Models\Buyer::where('id', $contract->buyer_id)->first();
                $val->buyer = $buyer->name;
                $vendor = \App\Models\Company::where('id', $contract->company_id)->first();
                $val->vendor = $vendor->title;
                $techpack = \App\Models\Techpack::where('id', $val->techpack_id)->first();
                $val->techpack = $techpack->title;

                $user = \App\Models\User::where('id', $val->user_id)->first();
                $val->user = $user->full_name;
            }
            $return['data'] = $purchases;
            $return['all_items'] = $all_purchase;

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
            $purchase = new Purchase;
            // Validate input
            $validator = Validator::make($request->all(), [
                        'contract_id' => 'required',
                        'po_number' => 'required',
                        'techpack_id' => 'required',
                        'sizes' => 'nullable',
                        'colors' => 'nullable',
                        'shipping_method' => 'nullable',
                        'order_date' => 'required',
                        'shipment_date' => 'required',
                        'lead_time' => 'required',
                        'booking_time' => 'required',
                        'material_inhouse_time' => 'required',
                        'production_time' => 'required',
                        'save_time' => 'required',
                        'delivery_address' => 'nullable',
                        'packing_instructions' => 'nullable',
                        'packing_method' => 'nullable',
                        'comment' => 'nullable',
            ]);

            if ($validator->fails()) {
                return response()->json(['errors' => $validator->errors()], 422);
            }
            // Update purchase attributes

            $purchase->user_id = $request->user->id;
            $purchase->contract_id = $request->input('contract_id');
            $purchase->po_number = $request->input('po_number');
            $purchase->techpack_id = $request->input('techpack_id');
            $purchase->sizes = $request->input('sizes');
            $purchase->colors = $request->input('colors');
            $purchase->shipping_method = $request->input('shipping_method');
            $purchase->order_date = $request->input('order_date');
            $purchase->shipment_date = $request->input('shipment_date');

            $purchase->lead_time = $request->input('lead_time');
            $purchase->booking_time = $request->input('booking_time');
            $purchase->material_inhouse_time = $request->input('material_inhouse_time');
            $purchase->production_time = $request->input('production_time');
            $purchase->save_time = $request->input('save_time');

            $purchase->delivery_address = $request->input('delivery_address');
            $purchase->packing_instructions = $request->input('packing_instructions');
            $purchase->packing_method = $request->input('packing_method');
            $purchase->comment = $request->input('comment');
            $purchase->save();
            $purchase_items = json_decode($request->input('purchase_items'));
            foreach ($purchase_items as $itemData) {
                $item = new PurchaseItem;
                $item->purchase_id = $purchase->id;
                $item->description = $itemData->description;
                $item->unit_price = $itemData->unit_price;
                $item->size = $itemData->size;
                $item->color = $itemData->color;
                $item->qty = $itemData->qty;
                $item->total = $itemData->total;
                $item->save();
            }


            $total_qty = PurchaseItem::where('purchase_id', $purchase->id)->sum('qty');
            $total_amount = PurchaseItem::where('purchase_id', $purchase->id)->sum('total');
            $purchase->total_qty = $total_qty;
            $purchase->total_amount = $total_amount;
            $purchase->save();

            if (request()->hasFile('attatchments')) {
                $files = request()->file('attatchments');
                foreach ($files as $file) {
                    $upload = new \App\Models\PurchaseFile();
                    $upload->purchase_id = $purchase->id;
                    $public_path = public_path();
                    $path = $public_path . '/' . "purchases";

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
            $statusCode = 200;
            $return['data'] = $purchase;
            $return['status'] = 'success';
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
            $purchase = Purchase::find($id);

            if ($purchase) {
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

                $return['data'] = $purchase;
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
            $purchase = Purchase::findOrFail($id); // Find the purchase record by ID
            // Validate input
            $validator = Validator::make($request->all(), [
                        'contract_id' => 'required',
                        'po_number' => 'required',
                        'techpack_id' => 'required',
                        'sizes' => 'nullable',
                        'colors' => 'nullable',
                        'shipping_method' => 'nullable',
                        'order_date' => 'required',
                        'shipment_date' => 'required',
                        'lead_time' => 'required',
                        'booking_time' => 'required',
                        'material_inhouse_time' => 'required',
                        'production_time' => 'required',
                        'save_time' => 'required',
                        'delivery_address' => 'nullable',
                        'packing_instructions' => 'nullable',
                        'packing_method' => 'nullable',
                        'comment' => 'nullable',
            ]);

            if ($validator->fails()) {
                return response()->json(['errors' => $validator->errors()], 422);
            }

            // Update purchase attributes
            $purchase->contract_id = $request->input('contract_id');
            $purchase->po_number = $request->input('po_number');
            $purchase->techpack_id = $request->input('techpack_id');
            $purchase->sizes = $request->input('sizes');
            $purchase->colors = $request->input('colors');
            $purchase->shipping_method = $request->input('shipping_method');
            $purchase->order_date = $request->input('order_date');
            $purchase->shipment_date = $request->input('shipment_date');
            $purchase->lead_time = $request->input('lead_time');
            $purchase->booking_time = $request->input('booking_time');
            $purchase->material_inhouse_time = $request->input('material_inhouse_time');
            $purchase->production_time = $request->input('production_time');
            $purchase->save_time = $request->input('save_time');
            $purchase->delivery_address = $request->input('delivery_address');
            $purchase->packing_instructions = $request->input('packing_instructions');
            $purchase->packing_method = $request->input('packing_method');
            $purchase->comment = $request->input('comment');

            $purchase->save(); // Save the updated purchase record
            // Update or delete related purchase items

            $purchase_items = json_decode($request->input('purchase_items'));

            // Delete items which are removed
            PurchaseItem::where('purchase_id', $purchase->id)->delete();
            foreach ($purchase_items as $itemData) {
                $item = new PurchaseItem;
                $item->purchase_id = $purchase->id;
                $item->description = $itemData->description;
                $item->unit_price = $itemData->unit_price;
                $item->size = $itemData->size;
                $item->color = $itemData->color;
                $item->qty = $itemData->qty;
                $item->total = $itemData->total;
                $item->save();
            }

            // Update purchase total qty and amount
            $total_qty = PurchaseItem::where('purchase_id', $purchase->id)->sum('qty');
            $total_amount = PurchaseItem::where('purchase_id', $purchase->id)->sum('total');

            $purchase->total_qty = $total_qty;
            $purchase->total_amount = $total_amount;
            $purchase->save();

            if (request()->hasFile('attatchments')) {
                $files = request()->file('attatchments');
                foreach ($files as $file) {
                    $upload = new \App\Models\PurchaseFile();
                    $upload->purchase_id = $id;
                    $public_path = public_path();
                    $path = $public_path . '/' . "purchases";

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

            $statusCode = 200;
            $return['data'] = $purchase;
            $return['status'] = 'success';
            return $this->response($return, $statusCode);
        } catch (\Throwable $ex) {
            return $this->error(['status' => 'error', 'main_error_message' => $ex->getMessage()]);
        }
    }

}
