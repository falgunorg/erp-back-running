<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Costing;
use Illuminate\Support\Facades\Validator;
use App\Models\CostingItem;
use App\Models\TechnicalPackage;
use App\Models\Team;

class CostingController extends Controller {

    public function index(Request $request) {
        try {
            $from_date = $request->input('from_date');
            $to_date = $request->input('to_date');
            $status = $request->input('status');
            $techpack_id = $request->input('techpack_id');
            $filter_items = $request->input('filter_items');

            $query = Costing::with([
                        'items',
                        'user',
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

            $costings = $query->paginate(30);

            return $this->response([
                        'costings' => $costings
                            ], 200);
        } catch (\Throwable $ex) {
            return $this->error([
                        'status' => 'error',
                        'main_error_message' => $ex->getMessage()
            ]);
        }
    }

    public function public_index(Request $request) {
        $costings = Costing::with([
                    'items',
                    'user',
                    'techpack.buyer',
                    'techpack.company',
                    'techpack.materials'
                ])->orderBy('created_at', 'desc')->get();

        return $this->response([
                    'costings' => $costings
                        ], 200);
    }

    public function store(Request $request) {
        try {
            $statusCode = 422;
            $costingItems = json_decode($request->input('costing_items'));

            $validator = Validator::make($request->all(), [
                'technical_package_id' => 'required|unique:costings,technical_package_id',
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

            $techpack = TechnicalPackage::find($request->input('technical_package_id'));
            if (!$techpack) {
                return response()->json(['error' => 'Technical Package not found.'], 404);
            }

            $costing = new Costing();
            $costing->user_id = $request->user->id;
            $costing->po_id = $request->input('po_id');
            $costing->wo_id = $request->input('wo_id');
            $costing->technical_package_id = $request->input('technical_package_id');
            $costing->factory_cpm = $request->input('factory_cpm');
            $costing->fob = $request->input('fob');
            $costing->cm = $request->input('cm');

            $costing->save();

            $costing->costing_ref = $techpack->techpack_number . '/' . $costing->id;
            $costing->save();

            if (is_array($costingItems)) {
                foreach ($costingItems as $val) {
                    // Skip items with missing required numeric fields
                    if ($val->supplier_id == "") {
                        $val->supplier_id = 0; // or skip this row with `continue;`
                    }
                    if ($val->consumption == "") {
                        $val->consumption = 0; // or skip this row with `continue;`
                    }

                    if ($val->total == "") {
                        $val->total = 0; // or skip this row with `continue;`
                    }

                    if ($val->unit_price == "") {
                        $val->unit_price = 0; // or skip this row with `continue;`
                    }
                    if ($val->total_price == "") {
                        $val->total_price = 0; // or skip this row with `continue;`
                    }


                    CostingItem::create([
                        'costing_id' => (int) $costing->id,
                        'item_type_id' => (int) ($val->item_type_id ?? 0),
                        'item_id' => (int) ($val->item_id ?? 0),
                        'item_name' => $val->item_name ?? '',
                        'item_details' => $val->item_details ?? '',
                        'color' => $val->color ?? '',
                        'size' => $val->size ?? '',
                        'unit' => $val->unit ?? '',
                        'position' => $val->position ?? '',
                        'supplier_id' => (int) $val->supplier_id, // now it's guaranteed numeric
                        'consumption' => $val->consumption,
                        'wastage' => $val->wastage,
                        'total' => $val->total,
                        'unit_price' => $val->unit_price,
                        'total_price' => $val->total_price,
                    ]);
                }
            }

            return response()->json([
                        'status' => 'success',
                        'data' => $costing
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
        $costing = Costing::with([
                    'items.supplier',
                    'items.item',
                    'user',
                    'techpack.buyer',
                    'techpack.company',
                ])->find($id);

        if (!$costing) {
            return response()->json(['error' => 'Costing not found.'], 404);
        }

        return response()->json([
                    'status' => 'success',
                    'data' => $costing
                        ], 200);
    }

    public function update(Request $request) {
        try {
            $id = $request->input('id');
            $costing = Costing::find($id);
            if (!$costing) {
                return response()->json(['error' => 'Costing not found.'], 404);
            }

            $validator = Validator::make($request->all(), [
                'fob' => 'required|numeric',
                'cm' => 'required|numeric',
            ]);

            if ($validator->fails()) {
                return response()->json(['errors' => $validator->errors()], 422);
            }

            $costing->update([
                'fob' => $request->input('fob'),
                'cm' => $request->input('cm'),
            ]);

            // Optionally update costing items
            if ($request->has('costing_items')) {
                $costing->items()->delete();

                $costingItems = json_decode($request->input('costing_items'));
                foreach ($costingItems as $val) {
                    // Skip items with missing required numeric fields
                    if ($val->supplier_id == "") {
                        $val->supplier_id = 0; // or skip this row with `continue;`
                    }
                    if ($val->consumption == "") {
                        $val->consumption = 0; // or skip this row with `continue;`
                    }

                    if ($val->total == "") {
                        $val->total = 0; // or skip this row with `continue;`
                    }

                    if ($val->unit_price == "") {
                        $val->unit_price = 0; // or skip this row with `continue;`
                    }
                    if ($val->total_price == "") {
                        $val->total_price = 0; // or skip this row with `continue;`
                    }


                    CostingItem::create([
                        'costing_id' => (int) $costing->id,
                        'item_type_id' => (int) ($val->item_type_id ?? 0),
                        'item_id' => (int) ($val->item_id ?? 0),
                        'item_name' => $val->item_name ?? '',
                        'item_details' => $val->item_details ?? '',
                        'color' => $val->color ?? '',
                        'size' => $val->size ?? '',
                        'unit' => $val->unit ?? '',
                        'position' => $val->position ?? '',
                        'supplier_id' => (int) $val->supplier_id, // now it's guaranteed numeric
                        'consumption' => $val->consumption,
                        'wastage' => $val->wastage,
                        'total' => $val->total,
                        'unit_price' => $val->unit_price,
                        'total_price' => $val->total_price,
                    ]);
                }
            }

            return response()->json([
                        'status' => 'success',
                        'data' => $costing
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
        $costing = Costing::find($id);
        if (!$costing) {
            return response()->json(['error' => 'Costing not found.'], 404);
        }

        $costing->items()->delete();
        $costing->delete();

        return response()->json([
                    'status' => 'success',
                    'message' => 'Costing deleted successfully.'
                        ], 200);
    }
}
