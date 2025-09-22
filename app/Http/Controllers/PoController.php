<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use App\Models\Po;
use App\Models\PoItem;
use App\Models\PoFile;

class PoController extends Controller {

    public function index(Request $request) {
        $statusCode = 200;
        // Paginate all technical packages with their relations
        $paginatedPos = Po::with('user', 'items', 'files', 'techpack.company', 'techpack.buyer', 'wo')
                ->orderBy('created_at', 'desc')
                ->where('user_id', $request->user->id)
                ->paginate(10);

        // Group the current page's items by department AFTER pagination
        $grouped = $paginatedPos->getCollection()->groupBy('department');

        // Replace collection with grouped result (optional, depends on your frontend)
        $paginatedPos->setCollection($grouped);

        return $this->response([
                    'pos' => $paginatedPos
                        ], $statusCode);
    }

    public function public_index(Request $request) {
        $technicalPackageId = $request->input('technical_package_id');
        $notIncludedOnWo = $request->input('not_included_on_wo');

        $query = Po::query()->orderBy('created_at', 'desc');

        if (!empty($technicalPackageId)) {
            $query->where('technical_package_id', $technicalPackageId);
        }

        if (!empty($notIncludedOnWo)) {
            $query->whereNull('wo_id');
        }

        $pos = $query->get();

        return response()->json([
                    'data' => $pos
                        ], 200);
    }

    public function store(Request $request) {
        $validator = Validator::make($request->all(), [
            'po_number' => 'required|unique:pos,po_number',
            'wo_id' => 'nullable',
            'issued_date' => 'required',
            'delivery_date' => 'required',
            'purchase_contract_id' => 'required',
            'technical_package_id' => 'required',
            'destination' => 'nullable',
            'ship_mode' => 'nullable',
            'shipping_terms' => 'nullable',
            'packing_method' => 'nullable',
            'payment_terms' => 'nullable',
            'total_qty' => 'required',
            'total_value' => 'required',
            'attatchments' => 'nullable|array',
            'attatchments.*' => 'file|max:5120',
            'po_items' => 'required',
        ]);

        if ($validator->fails()) {
            return response()->json([
                        'errors' => $validator->errors()
                            ], 422);
        }

        $po_items = json_decode($request->input('po_items'));

        $po = new Po();
        $po->user_id = $request->user->id;
        $po->po_number = $request->po_number;
        $po->wo_id = $request->wo_id;
        $po->issued_date = $request->issued_date;
        $po->delivery_date = $request->delivery_date;
        $po->purchase_contract_id = $request->purchase_contract_id;
        $po->technical_package_id = $request->technical_package_id;
        $po->destination = $request->destination;
        $po->ship_mode = $request->ship_mode;
        $po->shipping_terms = $request->shipping_terms;
        $po->packing_method = $request->packing_method;
        $po->payment_terms = $request->payment_terms;
        $po->total_qty = $request->total_qty;
        $po->total_value = $request->total_value;

        if ($po->save()) {
            foreach ($po_items as $val) {
                $item = new PoItem();
                $item->po_id = $po->id;
                $item->color = $val->color;
                $item->size = $val->size;
                $item->inseam = $val->inseam;
                $item->qty = $val->qty;
                $item->fob = $val->fob;
                $item->total = $val->total;
                $item->save();
            }
            if ($request->hasFile('attatchments')) {
                $files = $request->file('attatchments');

                foreach ($files as $index => $file) {
                    $upload = new PoFile();
                    $file_name = strtolower(str_replace(' ', '_', pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME))) . '.' . $file->getClientOriginalExtension();
                    $path = $file->storeAs('public/purchase_orders', $file_name);
                    $upload->po_id = $po->id;
                    $upload->filename = $file_name;

                    $upload->save();
                }
            }
        }


        $return['po'] = $po;
        $statusCode = 200;
        return $this->response($return, $statusCode);
    }

    public function show(Request $request) {
        $id = $request->input('id');
        $po = Po::with('user', 'items', 'files', 'techpack.company', 'techpack.buyer', 'wo', 'contract', 'payment_term', 'shipping_term')->find($id);
        if ($po) {
            return response()->json(['po' => $po], 200);
        }

        return response()->json([
                    'message' => 'Purchase order not found.'
                        ], 422);
    }

    public function update(Request $request) {
        $id = $request->input('id');
        $po = Po::find($id);

        if (!$po) {
            return response()->json(['message' => 'Purchase Order not found.'], 404);
        }

        $validator = Validator::make($request->all(), [
            'po_number' => 'required|unique:pos,po_number,' . $id,
            'wo_id' => 'nullable',
            'issued_date' => 'required',
            'delivery_date' => 'required',
            'purchase_contract_id' => 'required',
            'technical_package_id' => 'required',
            'destination' => 'nullable',
            'ship_mode' => 'nullable',
            'shipping_terms' => 'nullable',
            'packing_method' => 'nullable',
            'payment_terms' => 'nullable',
            'total_qty' => 'required',
            'total_value' => 'required',
            'attatchments' => 'nullable|array',
            'attatchments.*' => 'file|max:5120',
            'po_items' => 'required',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $po_items = json_decode($request->input('po_items'));
        $po->po_number = $request->po_number;
        $po->wo_id = $request->wo_id;
        $po->issued_date = $request->issued_date;
        $po->delivery_date = $request->delivery_date;
        $po->purchase_contract_id = $request->purchase_contract_id;
        $po->technical_package_id = $request->technical_package_id;
        $po->destination = $request->destination;
        $po->ship_mode = $request->ship_mode;
        $po->shipping_terms = $request->shipping_terms;
        $po->packing_method = $request->packing_method;
        $po->payment_terms = $request->payment_terms;
        $po->total_qty = $request->total_qty;
        $po->total_value = $request->total_value;
        $po->user_id = $request->user->id; // Optional: only update if needed

        if ($po->save()) {
            // Delete old items first (optional depending on your update strategy)
            PoItem::where('po_id', $po->id)->delete();

            foreach ($po_items as $val) {
                $item = new PoItem();
                $item->po_id = $po->id;
                $item->color = $val->color;
                $item->size = $val->size;
                $item->inseam = $val->inseam;
                $item->qty = $val->qty;
                $item->fob = $val->fob;
                $item->total = $val->total;
                $item->save();
            }

            if ($request->hasFile('attatchments')) {
                // Optional: delete old files if needed
                $files = $request->file('attatchments');

                foreach ($files as $file) {
                    $upload = new PoFile;
                    $file_name = strtolower(str_replace(' ', '_', pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME))) . '.' . $file->getClientOriginalExtension();
                    $path = $file->storeAs('public/purchase_orders', $file_name);
                    $upload->po_id = $po->id;
                    $upload->filename = $file_name;
                    $upload->save();
                }
            }
        }

        return response()->json(['po' => $po], 200);
    }

    public function destroy(Request $request) {

        $id = $request->input('id');

        $po = Po::find($id);

        if (!$po) {
            return response()->json(['error' => 'Purchase Order not found'], 404);
        }

        // Delete related materials and files
        PoItem::where('po_id', $id)->delete();
        PoFile::where('po_id', $id)->delete();
        $po->delete();

        return response()->json(['message' => 'Purchase Order deleted successfully']);
    }

    public function destroy_multiple(Request $request) {
        $ids = $request->input('ids'); // expects an array of PO IDs

        if (!is_array($ids) || empty($ids)) {
            return response()->json(['error' => 'No Purchase Order IDs provided'], 400);
        }

        foreach ($ids as $id) {
            $po = Po::find($id);

            if ($po) {
                // Delete related materials and files
                PoItem::where('po_id', $id)->delete();
                PoFile::where('po_id', $id)->delete();
                $po->delete();
            }
        }

        return response()->json(['message' => 'Selected Purchase Orders deleted successfully']);
    }

    public function delete_file(Request $request) {
        $statusCode = 422;
        $return = [];

        $id = $request->input('id');

        // Find the file record first
        $file = PoFile::find($id);

        if ($file) {
            // Construct the path relative to the 'storage/app' directory
            $filePath = 'public/purchase_orders/' . $file->filename;

            // Delete the file from storage
            if (Storage::exists($filePath)) {
                Storage::delete($filePath);
            }

            // Delete the database record
            $file->delete();

            $statusCode = 200;
            $return['message'] = 'File and record deleted successfully.';
        } else {
            $return['message'] = 'File record not found.';
        }

        return $this->response($return, $statusCode);
    }
}
