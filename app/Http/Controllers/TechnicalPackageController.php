<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\TechnicalPackage;
use App\Models\TechnicalPackageFile;
use App\Models\TechnicalPackageMaterial;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;

class TechnicalPackageController extends Controller {

    public function index_bk(Request $request) {
        $statusCode = 422;
        $techpacks = TechnicalPackage::with('materials', 'files')->orderBy('created_at', 'desc')->paginate(10);
        $return['techpacks'] = $techpacks;
        $statusCode = 200;

        return $this->response($return, $statusCode);
    }

    public function index(Request $request) {
        $statusCode = 200;
        // Paginate all technical packages with their relations
        $paginatedTechpacks = TechnicalPackage::with('materials.item_type', 'files', 'company', 'buyer', 'po')
                ->orderBy('created_at', 'desc')
                ->paginate(10);

        // Group the current page's items by department AFTER pagination
        $grouped = $paginatedTechpacks->getCollection()->groupBy('department');

        // Replace collection with grouped result (optional, depends on your frontend)
        $paginatedTechpacks->setCollection($grouped);

        return $this->response([
                    'techpacks' => $paginatedTechpacks
                        ], $statusCode);
    }

    public function public_index(Request $request) {
        $statusCode = 422;
        $mode = $request->input('mode');
        $group = $request->input('group');

        $query = TechnicalPackage::orderBy('created_at', 'desc');

        if ($mode === 'self') {
            $query->where('user_id', $request->user->id);
        }

        if ($group === "costing_done") {
            // Filter only technical packages that have a costing entry
            $query->whereIn('id', function ($subquery) {
                $subquery->select('technical_package_id')
                        ->from('costings')
                        ->whereNotNull('technical_package_id');
            });
        }

        $data = $query->get();

        $statusCode = 200;
        return $this->response(['data' => $data], $statusCode);
    }

    public function store(Request $request) {
        $validator = Validator::make($request->all(), [
            'po_id' => 'nullable',
            'wo_id' => 'nullable',
            'received_date' => 'required',
            'techpack_number' => 'required|unique:technical_packages,techpack_number',
            'buyer_id' => 'required',
            'buyer_style_name' => 'required',
            'brand' => 'required',
            'item_name' => 'required',
            'season' => 'required',
            'item_type' => 'required',
            'department' => 'required',
            'description' => 'nullable',
            'company_id' => 'required',
            'wash_details' => 'nullable',
            'special_operation' => 'nullable',
            'front_photo' => 'required|file|mimes:jpeg,png,jpg,gif,svg|max:5120',
            'back_photo' => 'required|file|mimes:jpeg,png,jpg,gif,svg|max:5120',
            'attatchments' => 'nullable|array',
            'attatchments.*' => 'file|max:5120',
            'tp_items' => 'required',
        ]);

        if ($validator->fails()) {
            return response()->json([
                        'errors' => $validator->errors()
                            ], 422);
        }

        $tp_items = json_decode($request->input('tp_items'));

        $techpack = new TechnicalPackage;
        $techpack->po_id = $request->po_id;
        $techpack->wo_id = $request->wo_id;
        $techpack->received_date = $request->received_date;
        $techpack->techpack_number = $request->techpack_number;
        $techpack->buyer_id = $request->buyer_id;
        $techpack->buyer_style_name = $request->buyer_style_name;
        $techpack->brand = $request->brand;
        $techpack->item_name = $request->item_name;
        $techpack->season = $request->season;
        $techpack->item_type = $request->item_type;
        $techpack->department = $request->department;
        $techpack->description = $request->description;
        $techpack->company_id = $request->company_id;
        $techpack->wash_details = $request->wash_details;
        $techpack->special_operation = $request->special_operation;
        $techpack->user_id = $request->user->id;

        if ($request->hasFile('front_photo')) {
            $file = $request->file('front_photo');
            $file_name = strtolower(str_replace(' ', '_', pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME))) . '.' . $file->getClientOriginalExtension();
            $path = $file->storeAs('public/technical_packages', $file_name);
            $techpack->front_photo = $file_name;
        }

        if ($request->hasFile('back_photo')) {
            $file = $request->file('back_photo');
            $file_name = strtolower(str_replace(' ', '_', pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME))) . '.' . $file->getClientOriginalExtension();
            $path = $file->storeAs('public/technical_packages', $file_name);
            $techpack->back_photo = $file_name;
        }

        if ($techpack->save()) {
            foreach ($tp_items as $val) {
                $item = new TechnicalPackageMaterial;
                $item->technical_package_id = $techpack->id;
                $item->item_type_id = $val->item_type_id;
                $item->item_id = $val->item_id;
                $item->item_name = $val->item_name;
                $item->item_details = $val->item_details;
                $item->color = $val->color;
                $item->size = $val->size;
                $item->position = $val->position;
                $item->unit = $val->unit;
                $item->consumption = $val->consumption;
                $item->wastage = $val->wastage;
                $item->total = $val->total;
                $item->save();
            }

            if ($request->hasFile('attatchments')) {
                $files = $request->file('attatchments');
                $fileTypes = $request->input('file_types'); // this will be an array

                foreach ($files as $index => $file) {
                    $upload = new TechnicalPackageFile;
                    $file_name = strtolower(str_replace(' ', '_', pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME))) . '.' . $file->getClientOriginalExtension();
                    $path = $file->storeAs('public/technical_packages', $file_name);

                    $upload->technical_package_id = $techpack->id;
                    $upload->file_type = $fileTypes[$index] ?? null; // match the index
                    $upload->filename = $file_name;

                    $upload->save();
                }
            }
        }


        $return['techpack'] = $techpack;
        $statusCode = 200;
        return $this->response($return, $statusCode);
    }

    public function show(Request $request) {
        $techpack = TechnicalPackage::with(['materials.item_type', 'materials.item', 'files', 'buyer', 'company', 'po', 'wo', 'user'])->find($request->input('id'));

        if (!$techpack) {
            return response()->json(['error' => 'Technical Package not found'], 404);
        }
        return response()->json($techpack);
    }

    public function update(Request $request) {

        $id = $request->input('id');
        $techpack = TechnicalPackage::findOrFail($id);
        $validator = Validator::make($request->all(), [
            'id' => 'required',
            'po_id' => 'nullable',
            'wo_id' => 'nullable',
            'received_date' => 'required',
            'techpack_number' => 'required|unique:technical_packages,techpack_number,' . $techpack->id,
            'buyer_id' => 'required',
            'buyer_style_name' => 'required',
            'brand' => 'required',
            'item_name' => 'required',
            'season' => 'required',
            'item_type' => 'required',
            'department' => 'required',
            'description' => 'nullable',
            'company_id' => 'required',
            'wash_details' => 'nullable',
            'special_operation' => 'nullable',
            'front_photo' => 'nullable',
            'back_photo' => 'nullable',
            'attatchments' => 'nullable|array',
            'attatchments.*' => 'file|max:5120',
            'tp_items' => 'required',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $tp_items = json_decode($request->input('tp_items'));

        $techpack->po_id = $request->po_id;
        $techpack->wo_id = $request->wo_id;
        $techpack->received_date = $request->received_date;
        $techpack->techpack_number = $request->techpack_number;
        $techpack->buyer_id = $request->buyer_id;
        $techpack->buyer_style_name = $request->buyer_style_name;
        $techpack->brand = $request->brand;
        $techpack->item_name = $request->item_name;
        $techpack->season = $request->season;
        $techpack->item_type = $request->item_type;
        $techpack->department = $request->department;
        $techpack->description = $request->description;
        $techpack->company_id = $request->company_id;
        $techpack->wash_details = $request->wash_details;
        $techpack->special_operation = $request->special_operation;

        if ($request->hasFile('front_photo')) {
            $file = $request->file('front_photo');
            $file_name = strtolower(str_replace(' ', '_', pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME))) . '.' . $file->getClientOriginalExtension();
            $file->storeAs('public/technical_packages', $file_name);
            $techpack->front_photo = $file_name;
        }

        if ($request->hasFile('back_photo')) {
            $file = $request->file('back_photo');
            $file_name = strtolower(str_replace(' ', '_', pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME))) . '.' . $file->getClientOriginalExtension();
            $file->storeAs('public/technical_packages', $file_name);
            $techpack->back_photo = $file_name;
        }

        $techpack->save();
        // Delete old items and re-insert
        TechnicalPackageMaterial::where('technical_package_id', $techpack->id)->delete();
        foreach ($tp_items as $val) {
            $item = new TechnicalPackageMaterial;
            $item->technical_package_id = $techpack->id;
            $item->item_type_id = $val->item_type_id;
            $item->item_id = $val->item_id;
            $item->item_name = $val->item_name;
            $item->item_details = $val->item_details;
            $item->color = $val->color;
            $item->size = $val->size;
            $item->position = $val->position;
            $item->unit = $val->unit;
            $item->consumption = $val->consumption;
            $item->wastage = $val->wastage;
            $item->total = $val->total;
            $item->save();
        }

        if ($request->hasFile('attatchments')) {
            $files = $request->file('attatchments');
            $fileTypes = $request->input('file_types');

            foreach ($files as $index => $file) {
                $upload = new TechnicalPackageFile;
                $file_name = strtolower(str_replace(' ', '_', pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME))) . '.' . $file->getClientOriginalExtension();
                $file->storeAs('public/technical_packages', $file_name);

                $upload->technical_package_id = $techpack->id;
                $upload->file_type = $fileTypes[$index] ?? null;
                $upload->filename = $file_name;
                $upload->save();
            }
        }
        return $this->response(['techpack' => $techpack], 200);
    }

    public function destroy(Request $request) {

        $id = $request->input('id');

        $techpack = TechnicalPackage::find($id);

        if (!$techpack) {
            return response()->json(['error' => 'Technical Package not found'], 404);
        }

        // Delete related materials and files
        TechnicalPackageMaterial::where('technical_package_id', $id)->delete();
        TechnicalPackageFile::where('technical_package_id', $id)->delete();
        $techpack->delete();

        return response()->json(['message' => 'Technical Package deleted successfully']);
    }

    public function destroy_multiple(Request $request) {
        $ids = $request->input('ids'); // expects an array of IDs

        if (!is_array($ids) || empty($ids)) {
            return response()->json(['error' => 'No Technical Package IDs provided'], 400);
        }

        foreach ($ids as $id) {
            $techpack = TechnicalPackage::find($id);

            if ($techpack) {
                // Delete related materials and files
                TechnicalPackageMaterial::where('technical_package_id', $id)->delete();
                TechnicalPackageFile::where('technical_package_id', $id)->delete();
                $techpack->delete();
            }
        }

        return response()->json(['message' => 'Selected Technical Packages deleted successfully']);
    }

    public function delete_file(Request $request) {
        $statusCode = 422;
        $return = [];

        $id = $request->input('id');

        // Find the file record first
        $file = TechnicalPackageFile::find($id);

        if ($file) {
            // Construct the path relative to the 'storage/app' directory
            $filePath = 'public/technical_packages/' . $file->filename;

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
