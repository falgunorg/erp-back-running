<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\SampleStore;
use App\Models\SampleBalance;
use App\Models\SampleStoreActivity;
use Illuminate\Support\Facades\Validator;
use App\Models\Team;
use Intervention\Image\Facades\Image;

class SampleStoreController extends Controller {

    public function admin_index(Request $request) {
        try {
            $statusCode = 422;
            $return = [];
            $user_id = $request->user->id;
            $from_date = $request->input('from_date');
            $to_date = $request->input('to_date');
            $num_of_row = $request->input('num_of_row');
            $filter_items = $request->input('filter_items');
            $buyer_id = $request->input('buyer_id');
            $item_type = $request->input('item_type');
            $techpack_id = $request->input('techpack_id');
            $query = SampleStore::orderBy('created_at', 'desc');

            // Apply the date range filter if both "from_date" and "to_date" are provided
            if ($from_date && $to_date) {
                $query->whereBetween('created_at', [$from_date, $to_date]);
            }
            if ($techpack_id) {
                $query->where('techpack_id', $techpack_id);
            }

            if ($item_type) {
                $query->where('item_type', $item_type);
            }
            if ($filter_items) {
                $query->whereIn('id', $filter_items);
            }
            if ($buyer_id) {
                $query->where('buyer_id', $buyer_id);
            }
            // Limit the result to "num_of_row" records
            $stores = $query->take($num_of_row)->get();
            // Process and modify the result
            foreach ($stores as $val) {
                $buyer = \App\Models\Buyer::where('id', $val->buyer_id)->first();
                $user = \App\Models\User::where('id', $val->user_id)->first();
                $val->buyer = $buyer->name;
                $val->user = $user->full_name;
                $item = \App\Models\Item::where('id', $val->item_type)->first();
                $val->item_type_name = $item->title;
                $techpack = \App\Models\Techpack::where('id', $val->techpack_id)->first();
                $val->techpack = $techpack->title;
                $reference = \App\Models\User::where('id', $val->reference)->first();
                $val->reference_name = $reference->full_name;
                $val->file_source = url('') . '/sample-stores/' . $val->photo;
                $store_balance = SampleBalance::where('sample_store_id', $val->id)->first();
                $val->balance = $store_balance->qty;
                $val->used = SampleStoreActivity::where('sample_store_id', $val)->where('type', 'Issue')->sum('qty');
            }
            $return['data'] = $stores;
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
            $user_id = $request->user->id;
            $from_date = $request->input('from_date');
            $to_date = $request->input('to_date');
            $num_of_row = $request->input('num_of_row');
            $filter_items = $request->input('filter_items');
            $buyer_id = $request->input('buyer_id');
            $item_type = $request->input('item_type');
            $techpack_id = $request->input('techpack_id');

            // Retrieve the user's team if available
            $find_user_team = Team::whereRaw("FIND_IN_SET('$user_id', employees)")->first();
            // Build the query with filters and ordering
            $query = SampleStore::orderBy('created_at', 'desc');

            // Continue with the query where user_id = $user_id if not associated with any team
            if ($find_user_team) {
                $return['Team'] = $find_user_team;
                $team_users = explode(',', $find_user_team->employees);

                $query->whereIn('user_id', [$user_id])
                        ->orWhereIn('reference', $team_users);
            } else {
                $query->where('user_id', $user_id);
            }

            // Apply the date range filter if both "from_date" and "to_date" are provided
            if ($from_date && $to_date) {
                $query->whereBetween('created_at', [$from_date, $to_date]);
            }
            if ($techpack_id) {
                $query->where('techpack_id', $techpack_id);
            }

            if ($item_type) {
                $query->where('item_type', $item_type);
            }
            if ($filter_items) {
                $query->whereIn('id', $filter_items);
            }
            if ($buyer_id) {
                $query->where('buyer_id', $buyer_id);
            }

            // Limit the result to "num_of_row" records
            $stores = $query->take($num_of_row)->get();

            // Retrieve all data without additional filters
            $allData = SampleStore::orderBy('created_at', 'desc')->get();

            // Process and modify the result

            foreach ($stores as $val) {
                $buyer = \App\Models\Buyer::where('id', $val->buyer_id)->first();
                $user = \App\Models\User::where('id', $val->user_id)->first();
                $val->buyer = $buyer->name;
                $val->user = $user->full_name;
                $item = \App\Models\Item::where('id', $val->item_type)->first();
                $val->item_type_name = $item->title;
                $techpack = \App\Models\Techpack::where('id', $val->techpack_id)->first();
                $val->techpack = $techpack->title;
                $reference = \App\Models\User::where('id', $val->reference)->first();
                $val->reference_name = $reference->full_name;
                $val->file_source = url('') . '/sample-stores/' . $val->photo;
                $store_balance = SampleBalance::where('sample_store_id', $val->id)->first();
                $val->balance = $store_balance->qty;
                $val->used = SampleStoreActivity::where('sample_store_id', $val)->where('type', 'Issue')->sum('qty');
            }
            $return['data'] = $stores;
            $return['allData'] = $allData;
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
            $user_id = $request->user->id;
            $validator = Validator::make($request->all(), [
                        'title' => 'required',
                        'item_type' => 'required',
                        'code' => 'required',
                        'buyer_id' => 'required',
                        'techpack_id' => 'required',
                        'color' => 'required',
                        'size' => 'nullable',
                        'qty' => 'required',
                        'unit' => 'required',
                        'reference' => 'required',
                        'description' => 'nullable',
            ]);

            if ($validator->fails()) {
                return response()->json(['errors' => $validator->errors()], 422);
            }
            $store = new SampleStore;
            $store->user_id = $user_id;
            $store->title = $request->input('title');
            $store->item_type = $request->input('item_type');
            $store->code = $request->input('code');
            $store->buyer_id = $request->input('buyer_id');
            $store->techpack_id = $request->input('techpack_id');
            $store->color = $request->input('color');
            $store->size = $request->input('size');
            $store->unit = $request->input('unit');
            $store->reference = $request->input('reference');
            $store->description = $request->input('description');

            if (isset($_FILES['photo']['name'])) {
                $public_path = public_path();
                $path = $public_path . '/' . "sample-stores";
                $pathinfo = pathinfo($_FILES['photo']['name']);
                $basename = strtolower(str_replace(' ', '_', $pathinfo['filename']));
                $extension = strtolower($pathinfo['extension']);
                $file_name = $basename . '.' . $extension;
                $finalpath = $path . '/' . $file_name;
                if (file_exists($finalpath)) {
                    $file_name = $basename . time() . '.' . $extension;
                    $finalpath = $path . '/' . $file_name;
                }
                if (move_uploaded_file($_FILES['photo']['tmp_name'], $finalpath)) {
                    $store->photo = $file_name;
                }
            }
            if ($store->save()) {
                $balance = new SampleBalance;
                $balance->sample_store_id = $store->id;
                $balance->qty = $request->input('qty');
                $balance->save();
            }

            $return['data'] = $store;
            $statusCode = 200;
            $return['status'] = 'success';
            return response()->json($return, $statusCode);
        } catch (\Throwable $ex) {
            return $this->error(['status' => 'error', 'main_error_message' => $ex->getMessage()]);
        }
    }

    public function show(Request $request) {
        try {
            $statusCode = 422;
            $return = [];
            $id = $request->input('id');
            $store = SampleStore::find($id);

            if ($store) {
                $buyer = \App\Models\Buyer::where('id', $store->buyer_id)->first();
                $user = \App\Models\User::where('id', $store->user_id)->first();
                $store->buyer = $buyer->name;
                $store->user = $user->full_name;

                $item = \App\Models\Item::where('id', $store->item_type)->first();
                $store->item_type_name = $item->title;

                $techpack = \App\Models\Techpack::where('id', $store->techpack_id)->first();
                $store->techpack = $techpack->title;

                $reference = \App\Models\User::where('id', $store->reference)->first();
                $store->reference_name = $reference->full_name;

                $store->file_source = url('') . '/sample-stores/' . $store->photo;

                $store_balance = SampleBalance::where('sample_store_id', $store->id)->first();
                $store->balance = $store_balance->qty;
                $store->qty = $store_balance->qty;

                $activities = SampleStoreActivity::where('sample_store_id', $store->id)->orderBy('created_at', 'desc')->get();

                foreach ($activities as $activity) {
                    $user = \App\Models\User::where('id', $activity->user_id)->first();
                    $activity->user = $user->full_name;

                    if ($activity->sor_id) {
                        $sor = \App\Models\Sor::where('id', $activity->sor_id)->first();
                        if ($sor) {
                            $activity->sor_number = $sor->sor_number;
                        } else {
                            $activity->sor_number = "";
                        }
                    }
                }
                $store->activities = $activities;

                $return['data'] = $store;
                $statusCode = 200;
                $return['status'] = 'success';
            } else {
                $return['status'] = 'error';
            }

            return $this->response($return, $statusCode);
        } catch (\Throwable $ex) {
            return $this->error(['status' => 'error', 'main_error_message' => $ex->getMessage()]);
        }
    }

    public function admin_show(Request $request) {
        try {
            $statusCode = 422;
            $return = [];
            $id = $request->input('id');
            $store = SampleStore::find($id);

            if ($store) {
                $buyer = \App\Models\Buyer::where('id', $store->buyer_id)->first();
                $user = \App\Models\User::where('id', $store->user_id)->first();
                $store->buyer = $buyer->name;
                $store->user = $user->full_name;

                $item = \App\Models\Item::where('id', $store->item_type)->first();
                $store->item_type_name = $item->title;

                $techpack = \App\Models\Techpack::where('id', $store->techpack_id)->first();
                $store->techpack = $techpack->title;

                $reference = \App\Models\User::where('id', $store->reference)->first();
                $store->reference_name = $reference->full_name;

                $store->file_source = url('') . '/sample-stores/' . $store->photo;

                $store_balance = SampleBalance::where('sample_store_id', $store->id)->first();
                $store->balance = $store_balance->qty;
                $store->qty = $store_balance->qty;

                $activities = SampleStoreActivity::where('sample_store_id', $store->id)->orderBy('created_at', 'desc')->get();

                foreach ($activities as $activity) {
                    $user = \App\Models\User::where('id', $activity->user_id)->first();
                    $activity->user = $user->full_name;

                    if ($activity->sor_id) {
                        $sor = \App\Models\Sor::where('id', $activity->sor_id)->first();
                        if ($sor) {
                            $activity->sor_number = $sor->sor_number;
                        } else {
                            $activity->sor_number = "";
                        }
                    }
                }
                $store->activities = $activities;

                $return['data'] = $store;
                $statusCode = 200;
                $return['status'] = 'success';
            } else {
                $return['status'] = 'error';
            }

            return $this->response($return, $statusCode);
        } catch (\Throwable $ex) {
            return $this->error(['status' => 'error', 'main_error_message' => $ex->getMessage()]);
        }
    }

    public function update(Request $request) {
        try {
            $statusCode = 422;
            $return = [];
            $user_id = $request->user->id;
            $id = $request->input('id');
            $validator = Validator::make($request->all(), [
                        'title' => 'required',
                        'item_type' => 'required',
                        'code' => 'required',
                        'buyer_id' => 'required',
                        'techpack_id' => 'required',
                        'color' => 'required',
                        'size' => 'nullable',
                        'qty' => 'required',
                        'unit' => 'required',
                        'reference' => 'required',
                        'description' => 'nullable',
            ]);

            if ($validator->fails()) {
                return response()->json(['errors' => $validator->errors()], 422);
            }
            $store = SampleStore::where('id', $id)->first();
            $store->user_id = $user_id;
            $store->title = $request->input('title');
            $store->item_type = $request->input('item_type');
            $store->code = $request->input('code');
            $store->buyer_id = $request->input('buyer_id');
            $store->techpack_id = $request->input('techpack_id');
            $store->color = $request->input('color');
            $store->size = $request->input('size');
            $store->unit = $request->input('unit');
            $store->reference = $request->input('reference');
            $store->description = $request->input('description');

            if (isset($_FILES['photo']['name'])) {
                $public_path = public_path();
                $path = $public_path . '/' . "sample-stores";
                $pathinfo = pathinfo($_FILES['photo']['name']);
                $basename = strtolower(str_replace(' ', '_', $pathinfo['filename']));
                $extension = strtolower($pathinfo['extension']);
                $file_name = $basename . '.' . $extension;
                $finalpath = $path . '/' . $file_name;
                if (file_exists($finalpath)) {
                    $file_name = $basename . time() . '.' . $extension;
                    $finalpath = $path . '/' . $file_name;
                }
                if (move_uploaded_file($_FILES['photo']['tmp_name'], $finalpath)) {
                    $store->photo = $file_name;
                }
            }
            if ($store->save()) {
                $balance = SampleBalance::where('sample_store_id', $store->id)->first();
                $balance->qty = $request->input('qty');
                $balance->save();
            }
            $return['data'] = $store;
            $statusCode = 200;
            $return['status'] = 'success';
            return response()->json($return, $statusCode);
        } catch (\Throwable $ex) {
            return $this->error(['status' => 'error', 'main_error_message' => $ex->getMessage()]);
        }
    }

    public function increment(Request $request) {
        try {
            $statusCode = 422;
            $return = [];
            $user_id = $request->user->id;
            $sample_store_id = $request->input('sample_store_id');
            $qty = $request->input('qty');
            $type = 'Add';
            $reference = $request->input('reference');
            $remarks = $request->input('remarks');

            $validator = Validator::make($request->all(), [
                        'sample_store_id' => 'required',
                        'qty' => 'required',
                        'reference' => 'nullable',
                        'remarks' => 'nullable',
            ]);

            if ($validator->fails()) {
                return response()->json(['errors' => $validator->errors()], 422);
            }

            $balance = SampleBalance::where('sample_store_id', $sample_store_id)->firstOrFail();

            if ($balance->increment('qty', $qty)) {
                $activity = new SampleStoreActivity;
                $activity->user_id = $user_id;
                $activity->sample_store_id = $sample_store_id;
                $activity->type = $type;
                $activity->qty = $qty;
                $activity->reference = $reference;
                $activity->remarks = $remarks;
                $activity->save();
            }

            $return['data'] = $balance; // Change $store to $balance
            $statusCode = 200;
            $return['status'] = 'success';
            return response()->json($return, $statusCode);
        } catch (\Throwable $ex) {
            return $this->error(['status' => 'error', 'main_error_message' => $ex->getMessage()]);
        }
    }

    public function inject_from_main_store(Request $request) {
        try {
            $statusCode = 422;
            $return = [];
            $user_id = $request->user->id;
            $validator = Validator::make($request->all(), [
                        'description' => 'required',
                        'id' => 'required',
                        'budget_item_id' => 'required',
                        'code' => 'required',
                        'buyer_id' => 'required',
                        'techpack_id' => 'required',
                        'color' => 'required',
                        'size' => 'nullable',
                        'qty' => 'required',
                        'unit' => 'required',
                        'short_description' => 'nullable',
            ]);

            if ($validator->fails()) {
                return response()->json(['errors' => $validator->errors()], 422);
            }
            $store = new SampleStore;
            $store->user_id = $user_id;
            $store->title = $request->input('description');
            $store->item_type = $request->input('budget_item_id');
            $store->code = $request->input('code');
            $store->buyer_id = $request->input('buyer_id');
            $store->techpack_id = $request->input('techpack_id');
            $store->color = $request->input('color');
            $store->size = $request->input('size');
            $store->unit = $request->input('unit');
            $store->reference = $user_id;
            $store->description = $request->input('short_description');
            $url = $request->input('image_source');
            $image = Image::make($url);
            $file_name = time() . '_' . $image->extension;
// Save the image to the sample-stores directory in the public disk
            $image->save(public_path('sample-stores/' . $file_name));
            $store->photo = $file_name;
            if ($store->save()) {

                $issue = \App\Models\Issue::where('id', $request->input('id'))->first();
                $issue->status = "Received";
                $issue->save();

                $balance = new SampleBalance;
                $balance->sample_store_id = $store->id;
                $balance->qty = $request->input('qty');
                $balance->save();
            }

            $return['data'] = $store;
            $statusCode = 200;
            $return['status'] = 'success';
            return response()->json($return, $statusCode);
        } catch (\Throwable $ex) {
            return $this->error(['status' => 'error', 'main_error_message' => $ex->getMessage()]);
        }
    }

}
