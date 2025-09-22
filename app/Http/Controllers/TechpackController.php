<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Techpack;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\DB;
use App\Models\TechpackItem;
use App\Models\Team;

class TechpackController extends Controller {

    public function admin_index(Request $request) {
        try {
            $statusCode = 422;
            $return = [];
            $buyer = $request->input('buyer_id');
            $from_date = $request->input('from_date');
            $to_date = $request->input('to_date');
            $status = $request->input('status');
            $num_of_row = $request->input('num_of_row');

            // Query builder instance
            $query = Techpack::orderBy('created_at', 'desc');

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
            $techpacks = $query->take($num_of_row)->get();
            foreach ($techpacks as $val) {
                $buyer = \App\Models\Buyer::where('id', $val->buyer_id)->first();
                $val->buyer = $buyer->name;

                $val->file_source = url('') . '/techpacks/' . $val->photo;
            }
            $return['data'] = $techpacks;
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
            $buyer = $request->input('buyer_id');
            $from_date = $request->input('from_date');
            $to_date = $request->input('to_date');
            $status = $request->input('status');
            $num_of_row = $request->input('num_of_row');
            $contract_id = $request->input('contract_id');
            $department = $request->input('department');
            $designation = $request->input('designation');
            $view = $request->input('view');
            $user_id = $request->user->id;
//         all items 
            $all_techpack = Techpack::all();
            // Query builder instance
            $query = Techpack::orderBy('created_at', 'desc');
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
                $query->whereBetween('created_at', [$from_date, $to_date]);
            }

            if ($buyer) {
                $query->where('buyer_id', $buyer);
            }

            if ($contract_id) {
                $contract = \App\Models\PurchaseContract::where('id', $contract_id)->first();
                $query->where('buyer_id', $contract->buyer_id);
            }



            $techpacks = $query->take($num_of_row)->get();

            foreach ($techpacks as $val) {
                $buyer = \App\Models\Buyer::where('id', $val->buyer_id)->first();
                $val->buyer = $buyer->name;
                $val->file_source = url('storage/techpacks/' . $val->photo);
                $user = \App\Models\User::where('id', $val->user_id)->first();
                $val->user = $user->full_name;
            }
            $return['data'] = $techpacks;
            $return['all_items'] = $all_techpack;
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

            // Validate input
            $validator = Validator::make($request->all(), [
                'title' => 'required|unique:techpacks,title',
                'buyer_id' => 'required',
                'season' => 'required',
                'item_type' => 'required',
                'item_name' => 'required',
                'sizes' => 'required',
                'wash_details' => 'required',
                'operations' => 'nullable',
                'description' => 'nullable|string',
                'fds_shrinkage_length' => 'nullable',
                'fds_shrinkage_width' => 'nullable',
                'fds_gsm' => 'nullable',
                'fds_width' => 'nullable',
                'fds_composition' => 'nullable',
                'photo' => 'required|file|mimes:jpeg,png,jpg,gif,svg|max:5120',
                'techpack_file' => 'nullable|file|max:5120',
                'specsheet' => 'nullable|file|max:5120',
                'block_pattern' => 'nullable|file|max:5120',
                'attatchments' => 'nullable|array',
                'attatchments.*' => 'file|max:5120'
            ]);

            if ($validator->fails()) {
                return response()->json(['errors' => $validator->errors()], 422);
            }

            // Create a new Techpack
            $techpack = new Techpack;
            $techpack->user_id = $request->user->id;
            $techpack->title = $request->input('title');
            $techpack->buyer_id = $request->input('buyer_id');
            $techpack->season = $request->input('season');
            $techpack->item_type = $request->input('item_type');
            $techpack->description = $request->input('description', '');
            $techpack->item_name = $request->input('item_name');
            $techpack->sizes = $request->input('sizes', '');
            $techpack->wash_details = $request->input('wash_details', '');
            $techpack->operations = $request->input('operations', '');
            $techpack->fds_shrinkage_length = $request->input('fds_shrinkage_length', '');
            $techpack->fds_shrinkage_width = $request->input('fds_shrinkage_width', '');
            $techpack->fds_gsm = $request->input('fds_gsm', '');
            $techpack->fds_width = $request->input('fds_width', '');
            $techpack->fds_composition = $request->input('fds_composition', '');
            $techpack->status = "Pending";

            // Handle photo upload
            if ($request->hasFile('photo')) {
                $photo = $request->file('photo');
                $file_name = strtolower(str_replace(' ', '_', pathinfo($photo->getClientOriginalName(), PATHINFO_FILENAME))) . '.' . $photo->getClientOriginalExtension();
                $path = $photo->storeAs('public/techpacks', $file_name);
                $techpack->photo = $file_name;
            }

            // Handle techpack file upload
            if ($request->hasFile('techpack_file')) {
                $techpackFile = $request->file('techpack_file');
                $file_name = strtolower(str_replace(' ', '_', pathinfo($techpackFile->getClientOriginalName(), PATHINFO_FILENAME))) . '.' . $techpackFile->getClientOriginalExtension();
                $path = $techpackFile->storeAs('public/techpacks', $file_name);
                $techpack->techpack_file = $file_name;
            }

            // Handle specsheet file upload
            if ($request->hasFile('specsheet')) {
                $specsheet = $request->file('specsheet');
                $file_name = strtolower(str_replace(' ', '_', pathinfo($specsheet->getClientOriginalName(), PATHINFO_FILENAME))) . '.' . $specsheet->getClientOriginalExtension();
                $path = $specsheet->storeAs('public/techpacks', $file_name);
                $techpack->specsheet = $file_name;
            }

            // Handle block pattern file upload
            if ($request->hasFile('block_pattern')) {
                $blockPattern = $request->file('block_pattern');
                $file_name = strtolower(str_replace(' ', '_', pathinfo($blockPattern->getClientOriginalName(), PATHINFO_FILENAME))) . '.' . $blockPattern->getClientOriginalExtension();
                $path = $blockPattern->storeAs('public/techpacks', $file_name);
                $techpack->block_pattern = $file_name;
            }

            // Save techpack to DB
            $techpack->save();

            // Handle multiple attachments upload
            if ($request->hasFile('attatchments')) {
                $files = $request->file('attatchments');
                foreach ($files as $file) {
                    $upload = new \App\Models\TechpackFile();
                    $file_name = strtolower(str_replace(' ', '_', pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME))) . '.' . $file->getClientOriginalExtension();
                    $path = $file->storeAs('public/techpacks', $file_name);
                    $upload->techpack_id = $techpack->id;
                    $upload->filename = $file_name;
                    $upload->save();
                }
            }

            $statusCode = 200;
            $return['data'] = $techpack;
            $return['status'] = 'success';
            return $this->response($return, $statusCode);
        } catch (\Throwable $ex) {
            return $this->error(['status' => 'error', 'main_error_message' => $ex->getMessage()]);
        }
    }

    public function update(Request $request) {
        try {
            $statusCode = 422;
            $return = [];
            $id = $request->input('id');

            // Validate input
            $validator = Validator::make($request->all(), [
                'title' => 'required|unique:techpacks,title,' . $id,
                'buyer_id' => 'required',
                'season' => 'required',
                'item_type' => 'required',
                'item_name' => 'required',
                'sizes' => 'required',
                'wash_details' => 'required',
                'operations' => 'nullable',
                'description' => 'nullable|string',
                'fds_shrinkage_length' => 'nullable',
                'fds_shrinkage_width' => 'nullable',
                'fds_gsm' => 'nullable',
                'fds_width' => 'nullable',
                'fds_composition' => 'nullable',
                'photo' => 'nullable|file|mimes:jpeg,png,jpg,gif,svg|max:5120', // Photo is now optional for update
                'techpack_file' => 'nullable|file|max:5120',
                'specsheet' => 'nullable|file|max:5120',
                'block_pattern' => 'nullable|file|max:5120',
                'attatchments' => 'nullable|array',
                'attatchments.*' => 'file|max:5120'
            ]);

            if ($validator->fails()) {
                return response()->json(['errors' => $validator->errors()], 422);
            }

            // Find the existing Techpack
            $techpack = Techpack::find($id);

            if (!$techpack) {
                return response()->json(['errors' => 'Techpack not found'], 404);
            }

            // Update Techpack details
            $techpack->user_id = $request->user->id;
            $techpack->title = $request->input('title', $techpack->title);
            $techpack->buyer_id = $request->input('buyer_id', $techpack->buyer_id);
            $techpack->season = $request->input('season', $techpack->season);
            $techpack->item_type = $request->input('item_type', $techpack->item_type);
            $techpack->description = $request->input('description', $techpack->description);
            $techpack->item_name = $request->input('item_name', $techpack->item_name);
            $techpack->sizes = $request->input('sizes', $techpack->sizes);
            $techpack->wash_details = $request->input('wash_details', $techpack->wash_details);
            $techpack->operations = $request->input('operations', $techpack->operations);
            $techpack->fds_shrinkage_length = $request->input('fds_shrinkage_length', $techpack->fds_shrinkage_length);
            $techpack->fds_shrinkage_width = $request->input('fds_shrinkage_width', $techpack->fds_shrinkage_width);
            $techpack->fds_gsm = $request->input('fds_gsm', $techpack->fds_gsm);
            $techpack->fds_width = $request->input('fds_width', $techpack->fds_width);
            $techpack->fds_composition = $request->input('fds_composition', $techpack->fds_composition);
            $techpack->status = "Pending";  // You might want to keep the current status or update it if needed.
            // Handle photo upload (if any new photo is uploaded)
            if ($request->hasFile('photo')) {
                // Delete old photo if exists
                if ($techpack->photo && file_exists(storage_path('app/public/techpacks/' . $techpack->photo))) {
                    unlink(storage_path('app/public/techpacks/' . $techpack->photo));
                }
                $photo = $request->file('photo');
                $file_name = strtolower(str_replace(' ', '_', pathinfo($photo->getClientOriginalName(), PATHINFO_FILENAME))) . '.' . $photo->getClientOriginalExtension();
                $path = $photo->storeAs('public/techpacks', $file_name);
                $techpack->photo = $file_name;
            }

            // Handle techpack file upload (if any new file is uploaded)
            if ($request->hasFile('techpack_file')) {
                if ($techpack->techpack_file && file_exists(storage_path('app/public/techpacks/' . $techpack->techpack_file))) {
                    unlink(storage_path('app/public/techpacks/' . $techpack->techpack_file));
                }
                $techpackFile = $request->file('techpack_file');
                $file_name = strtolower(str_replace(' ', '_', pathinfo($techpackFile->getClientOriginalName(), PATHINFO_FILENAME))) . '.' . $techpackFile->getClientOriginalExtension();
                $path = $techpackFile->storeAs('public/techpacks', $file_name);
                $techpack->techpack_file = $file_name;
            }

            // Handle specsheet file upload (if any new specsheet is uploaded)
            if ($request->hasFile('specsheet')) {
                if ($techpack->specsheet && file_exists(storage_path('app/public/techpacks/' . $techpack->specsheet))) {
                    unlink(storage_path('app/public/techpacks/' . $techpack->specsheet));
                }
                $specsheet = $request->file('specsheet');
                $file_name = strtolower(str_replace(' ', '_', pathinfo($specsheet->getClientOriginalName(), PATHINFO_FILENAME))) . '.' . $specsheet->getClientOriginalExtension();
                $path = $specsheet->storeAs('public/techpacks', $file_name);
                $techpack->specsheet = $file_name;
            }

            // Handle block pattern file upload (if any new block pattern is uploaded)
            if ($request->hasFile('block_pattern')) {
                if ($techpack->block_pattern && file_exists(storage_path('app/public/techpacks/' . $techpack->block_pattern))) {
                    unlink(storage_path('app/public/techpacks/' . $techpack->block_pattern));
                }
                $blockPattern = $request->file('block_pattern');
                $file_name = strtolower(str_replace(' ', '_', pathinfo($blockPattern->getClientOriginalName(), PATHINFO_FILENAME))) . '.' . $blockPattern->getClientOriginalExtension();
                $path = $blockPattern->storeAs('public/techpacks', $file_name);
                $techpack->block_pattern = $file_name;
            }

            // Save updated techpack to DB
            $techpack->save();

            // Handle multiple attachments upload
            if ($request->hasFile('attatchments')) {
                $files = $request->file('attatchments');
                foreach ($files as $file) {
                    $upload = new \App\Models\TechpackFile();
                    $file_name = strtolower(str_replace(' ', '_', pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME))) . '.' . $file->getClientOriginalExtension();
                    $path = $file->storeAs('public/techpacks', $file_name);
                    $upload->techpack_id = $techpack->id;
                    $upload->filename = $file_name;
                    $upload->save();
                }
            }

            $statusCode = 200;
            $return['data'] = $techpack;
            $return['status'] = 'success';
            return $this->response($return, $statusCode);
        } catch (\Throwable $ex) {
            return $this->error(['status' => 'error', 'main_error_message' => $ex->getMessage()]);
        }
    }

    public function show(Request $request) {
        try {
            $statusCode = 200;
            $return = [];
            $id = $request->input('id');
            $techpack = Techpack::findOrFail($id);
            if ($techpack) {
                $buyer = \App\Models\Buyer::where('id', $techpack->buyer_id)->first();
                $techpack->buyer = $buyer->name;
                $attatchments = \App\Models\TechpackFile::where('techpack_id', $techpack->id)->get();
                foreach ($attatchments as $item) {
                    $item->file_source = url('storage/techpacks/' . $item->filename);
                }
                $techpack->file_source = url('storage/techpacks/' . $techpack->photo);
                $techpack->techpack_file_url = url('storage/techpacks/' . $techpack->techpack_file);
                $techpack->specsheet_url = url('storage/techpacks/' . $techpack->specsheet);
                $techpack->block_pattern_url = url('storage/techpacks/' . $techpack->block_pattern);
                $techpack_user = \App\Models\User::where('id', $techpack->user_id)->first();
                $techpack->techpack_by = $techpack_user->full_name;
                $techpack->attatchments = $attatchments;

                $sizeArray = explode(',', $techpack->sizes);
                $sizes = \App\Models\Size::whereIn('id', $sizeArray)->get();
                $techpack->sizeList = $sizes;

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


            $return['data'] = $techpack;
            $return['status'] = 'success';
            return $this->response($return, $statusCode);
        } catch (\Throwable $ex) {
            return $this->error(['status' => 'error', 'main_error_message' => $ex->getMessage()]);
        }
    }

    public function destroy(Request $request) {
        try {
            $statusCode = 200;
            $return = [];
            $id = $request->input('id');
            $techpack = Techpack::findOrFail($id);
            $techpack->delete();
            $return['status'] = 'success';
            return $this->response($return, $statusCode);
        } catch (\Throwable $ex) {
            return $this->error(['status' => 'error', 'main_error_message' => $ex->getMessage()]);
        }
    }

    public function delete_attachment(Request $request) {
        try {
            $statusCode = 422;
            $return = [];
            $id = $request->input('id');
            $techpackFile = \App\Models\TechpackFile::findOrFail($id);

            // Get the file path
            $filePath = public_path('techpacks/') . $techpackFile->filename;

            // Check if file exists before deleting
            if (file_exists($filePath)) {
                // Delete the file
                unlink($filePath);
            }

            // Now, delete the database entry
            $techpackFile->delete();
            $statusCode = 200;
            $return['status'] = 'success';
            return response()->json($return, $statusCode);
        } catch (\Throwable $ex) {
            return response()->json(['status' => 'error', 'main_error_message' => $ex->getMessage()], $statusCode);
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
            $costing = Techpack::find($id);
            if (!$costing) {
                return response()->json([
                            'status' => 'error',
                            'main_error_message' => 'Techpack not found'
                                ], 404);
            }
            if ($status == "Placed") {
                $costing->placed_at = date('Y-m-d H:i:s');
                $notify_users = \App\Models\User::where('department', 18)
                                ->where('designation', 1)->get();
                if ($notify_users->isNotEmpty()) {
                    foreach ($notify_users as $notify_user) {
                        $notification = new \App\Models\Notification;
                        $notification->title = "A Techpack is Placed by " . $user->full_name;
                        $notification->receiver = $notify_user->id;
                        $notification->url = "/sample/consumptions";
                        $notification->description = "Please Take Necessary Action";
                        $notification->is_read = 0;
                        $notification->save();
                    }
                }
            } else if ($status == "Consumption Done") {
                $costing->consumption_by = $user_id;
                $costing->consumption_at = date('Y-m-d H:i:s');
                $notification = new \App\Models\Notification;
                $notification->title = "A Techpack Consumption Done by " . $user->full_name;
                $notification->receiver = $costing->user_id;
                $notification->url = "/merchandising/techpacks";
                $notification->description = "Please Take Necessary Action";
                $notification->is_read = 0;
                $notification->save();
            } else if ($status == "Costing Done") {
                $costing->costing_by = $user_id;
                $costing->costing_at = date('Y-m-d H:i:s');
            }
            $costing->status = $status;
            $costing->save();
            $statusCode = 200;
            $return['data'] = $costing;
            $return['status'] = 'success';
            return $this->response($return, $statusCode);
        } catch (\Throwable $ex) {
            return $this->error(['status' => "error", 'main_error_message' => $ex->getMessage()]);
        }
    }
}
