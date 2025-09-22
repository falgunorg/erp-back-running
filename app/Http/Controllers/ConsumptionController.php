<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Consumption;
use Illuminate\Support\Facades\Validator;
use App\Models\ConsumptionItem;

class ConsumptionController extends Controller {

    public function admin_index(Request $request) {
        try {
            $statusCode = 422;
            $return = [];
            $from_date = $request->input('from_date');
            $to_date = $request->input('to_date');
            $status = $request->input('status');
            $num_of_row = $request->input('num_of_row');

            // Query builder instance
            $query = Consumption::orderBy('created_at', 'desc');

            // Apply filters
            if ($status) {
                $query->where('status', $status);
            }
            if ($from_date && $to_date) {
                $query->whereBetween('order_date', [$from_date, $to_date]);
            }

            $consumptions = $query->take($num_of_row)->get();
            foreach ($consumptions as $val) {
                $techpack = \App\Models\Techpack::where('id', $val->techpack_id)->first();
                $val->teckpack = $techpack->title;

                $buyer = \App\Models\Buyer::where('id', $techpack->buyer_id)->first();
                $val->buyer = $buyer->name;
                $val->season = $techpack->season;

                $val->item_type = $techpack->item_type;
            }
            $return['data'] = $consumptions;
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
            $from_date = $request->input('from_date');
            $to_date = $request->input('to_date');
            $status = $request->input('status');
            $num_of_row = $request->input('num_of_row');
//         all items 

            $all_consumption = Consumption::all();
            // Query builder instance
            $query = Consumption::orderBy('created_at', 'desc');
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



            $consumptions = $query->take($num_of_row)->get();
            foreach ($consumptions as $val) {
                $techpack = \App\Models\Techpack::where('id', $val->techpack_id)->first();
                $val->teckpack = $techpack->title;
                $buyer = \App\Models\Buyer::where('id', $techpack->buyer_id)->first();
                $val->buyer = $buyer->name;
                $val->season = $techpack->season;
                $val->item_type = $techpack->item_type;
                $val->file_source = url('') . '/techpacks/' . $techpack->photo;
            }


            $return['data'] = $consumptions;
            $return['all_items'] = $all_consumption;
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
            $consumption = new Consumption;
            $user = \App\Models\User::find($request->user->id);
            // Validate input
            $validator = Validator::make($request->all(), [
                        'techpack_id' => 'required|unique:consumptions,techpack_id',
                        'description' => 'nullable',
            ]);

            if ($validator->fails()) {
                return response()->json(['errors' => $validator->errors()], 422);
            }
            $consumption_items = json_decode($request->input('consumption_items'));
            $consumption->user_id = $request->user->id;
            $consumption->techpack_id = $request->input('techpack_id');
            $consumption->description = $request->input('description');
            $consumption->save();

            if (request()->hasFile('attatchments')) {
                $files = request()->file('attatchments');
                foreach ($files as $file) {
                    $upload = new \App\Models\ConsumptionFile();
                    $upload->consumption_id = $consumption->id;
                    $public_path = public_path();
                    $path = $public_path . '/' . "consumptions";

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

            foreach ($consumption_items as $val) {
                $item = new ConsumptionItem;
                $item->consumption_id = $consumption->id;
                $item->item_id = $val->item_id;
                $item->description = $val->description;
                $item->unit = $val->unit;
                $item->size = $val->size;
                $item->color = $val->color;
                $item->qty = $val->qty;
                $item->save();
            }

            $techpack = \App\Models\Techpack::where('id', $consumption->techpack_id)->first();
            $techpack->status = "Consumption Done";
            $techpack->consumption_by = $request->user->id;
            $techpack->consumption_at = date('Y-m-d H:i:s');

            if ($techpack->save()) {
                $notification = new \App\Models\Notification;
                $notification->title = "A Techpack Consumption Done by " . $user->full_name;
                $notification->receiver = $techpack->user_id;
                $notification->url = "/merchandising/techpacks";
                $notification->description = "Please Take Necessary Action";
                $notification->is_read = 0;
                $notification->save();
            }

            $statusCode = 200;
            $return['data'] = $consumption;
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

            $consumption = Consumption::findOrFail($id);

            // Validate input
            $validator = Validator::make($request->all(), [
                        'techpack_id' => 'required|unique:consumptions,techpack_id,' . $id,
                        'description' => 'nullable',
            ]);

            if ($validator->fails()) {
                return response()->json(['errors' => $validator->errors()], 422);
            }
            $consumption_items = json_decode($request->input('consumption_items'));
            // Update consumption attributes

            $consumption->techpack_id = $request->input('techpack_id');
            $consumption->description = $request->input('description');
            $consumption->save();

            if (request()->hasFile('attatchments')) {
                $files = request()->file('attatchments');
                foreach ($files as $file) {
                    $upload = new \App\Models\ConsumptionFile();
                    $upload->consumption_id = $consumption->id;
                    $public_path = public_path();
                    $path = $public_path . '/' . "consumptions";

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

            ConsumptionItem::where('consumption_id', $consumption->id)->delete();
            foreach ($consumption_items as $val) {
                $item = new ConsumptionItem;
                $item->consumption_id = $consumption->id;
                $item->item_id = $val->item_id;
                $item->description = $val->description;
                $item->unit = $val->unit;
                $item->size = $val->size;
                $item->color = $val->color;
                $item->qty = $val->qty;
                $item->save();
            }

            $statusCode = 200;
            $return['data'] = $consumption;
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
            $consumption = Consumption::findOrFail($id);
            if ($consumption) {

                $techpack = \App\Models\Techpack::where('id', $consumption->techpack_id)->first();
                $techpack_user = \App\Models\User::where('id', $techpack->user_id)->first();

                $consumption->file_source = url('') . '/techpacks/' . $techpack->photo;
                $consumption->techpack_by = $techpack_user->full_name;
                $consumption->techpack_date = $techpack->created_at;
                $consumption->teckpack = $techpack->title;
                $consumption->season = $techpack->season;

                $consumption_user = \App\Models\User::where('id', $consumption->user_id)->first();
                $consumption->consumption_by = $consumption_user->full_name;

                $buyer = \App\Models\Buyer::where('id', $techpack->buyer_id)->first();
                $consumption->buyer = $buyer->name;

                $consumption_items = ConsumptionItem::where('consumption_id', $consumption->id)->get();

                foreach ($consumption_items as $val) {
                    $item = \App\Models\Item::where('id', $val->item_id)->first();
                    $val->item_name = $item->title;
                }

                $consumption->consumption_items = $consumption_items;
                $attatchments = \App\Models\ConsumptionFile::where('consumption_id', $consumption->id)->get();
                foreach ($attatchments as $item) {
                    $item->file_source = url('') . '/consumptions/' . $item->filename;
                }
                $consumption->attatchments = $attatchments;
            }
            $return['data'] = $consumption;
            $return['status'] = 'success';
            return $this->response($return, $statusCode);
        } catch (\Throwable $ex) {
            return $this->error(['status' => 'error', 'main_error_message' => $ex->getMessage()]);
        }
    }

    public function togglestatus(Request $request) {
        try {
            $statusCode = 422;
            $return = [];
            $id = $request->input('id');
            $status = $request->input('status');

            $consumption = Consumption::where('id', $id)->first();

            if ($consumption) {
                $consumption->status = $status;
                $consumption->save();

                $return['data'] = $consumption;
            }

            $statusCode = 200;
            $return['status'] = 'success';
            return response()->json($return, $statusCode);
        } catch (\Throwable $ex) {
            return response()->json(['status' => 'error', 'main_error_message' => $ex->getMessage()], $statusCode);
        }
    }

    public function destroy(Request $request) {
        try {
            $statusCode = 200;
            $return = [];
            $id = $request->input('id');
            $consumption = Consumption::findOrFail($id);
            $consumption->delete();
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
            $consumptionFile = \App\Models\ConsumptionFile::findOrFail($id);

            // Get the file path
            $filePath = public_path('consumptions/') . $consumptionFile->filename;

            // Check if file exists before deleting
            if (file_exists($filePath)) {
                // Delete the file
                unlink($filePath);
            }

            // Now, delete the database entry
            $consumptionFile->delete();
            $statusCode = 200;
            $return['status'] = 'success';
            return response()->json($return, $statusCode);
        } catch (\Throwable $ex) {
            return response()->json(['status' => 'error', 'main_error_message' => $ex->getMessage()], $statusCode);
        }
    }

}
