<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Part;
use Illuminate\Support\Facades\Validator;
use App\Models\User;

class PartController extends Controller {

    public function index(Request $request) {
        try {
            $statusCode = 422;
            $return = [];
            $user = User::find($request->user->id);
            $type = $request->input('type');
            $search = $request->input('search');

            $access = \App\Models\SubStoreAccess::where('user_id', $user->id)->first();
            $accessAreas = explode(',', $access->area);
            $query = Part::where('company_id', $user->company)->orderBy('created_at', 'desc');

            if ($type) {
                if (in_array($type, $accessAreas)) {
                    $query->where('type', $type);
                } else {
                    // If the type is not in access areas, return no results
                    $query->whereRaw('1 = 0');
                }
            } else {
                $query->whereIn('type', $accessAreas);
            }
            // Add search filter
            if ($search) {
                $query->where(function ($q) use ($search, $accessAreas) {
                    $q->where('title', 'LIKE', "%{$search}%")
                            ->whereIn('type', $accessAreas);
                });
            }

            $partlists = $query->paginate(200);
            foreach ($partlists as $val) {
                $val->image_source = url('') . '/parts/' . $val->photo;
            }




            $parts = Part::where('company_id', $user->company)->whereIn('type', $accessAreas)->orderBy('created_at', 'desc')->get();
            foreach ($parts as $val) {
                $stock = \App\Models\SubStore::where('part_id', $val->id)
                                ->where('company_id', $user->company)->sum('qty');
                $val->stock = $stock;
            }


            $all_data = Part::orderBy('created_at', 'desc')->get();
            foreach ($all_data as $val) {
                $stock = \App\Models\SubStore::where('part_id', $val->id)->sum('qty');
                $val->stock = $stock;
            }

            $return['all_data'] = $all_data;
            $return['data'] = $parts;
            $return['parts'] = $partlists;
            $statusCode = 200;
            return $this->response($return, $statusCode);
        } catch (\Throwable $ex) {
            return $this->error(['status' => 'error', 'main_error_message' => $ex->getMessage()]);
        }
    }

    public function index_bk(Request $request) {
        try {
            $statusCode = 422;
            $return = [];
            $user = User::find($request->user->id);
            $parts = Part::where('company_id', $user->company)->orderBy('created_at', 'desc')->get();
            if ($parts) {
                foreach ($parts as $val) {
                    $stock = \App\Models\SubStore::where('part_id', $val->id)
                                    ->where('company_id', $user->company)->sum('qty');
                    $val->stock = $stock;
                }
                $return['data'] = $parts;
                $statusCode = 200;
            } else {
                $return['status'] = 'error';
            }
            return $this->response($return, $statusCode);
        } catch (\Throwable $ex) {
            return $this->error(['status' => 'error', 'main_error_message' => $ex->getMessage()]);
        }
    }

    public function store(Request $request) {
        try {
            $statusCode = 422;
            $return = [];

            $user = User::find($request->user->id);

            // Custom validation rule to check for existing title and company_id combination
            $validator = Validator::make($request->all(), [
                'title' => [
                    'required',
                    function ($attribute, $value, $fail) use ($request, $user) {
                        if (Part::where('title', $value)->where('company_id', $user->company)->exists()) {
                            $fail('The title has already been taken for this company.');
                        }
                    },
                ],
                'unit' => 'required',
                'type' => 'required',
                'min_balance' => 'required',
                'brand' => 'nullable',
                'model' => 'nullable',
                'photo' => 'nullable',
            ]);

            if ($validator->fails()) {
                $return['errors'] = $validator->errors();
                $statusCode = 422;
                return $this->response($return, $statusCode);
            }

            $part = new Part;
            $part->user_id = $user->id;
            $part->company_id = $user->company;
            $part->title = strtoupper($request->input('title'));
            $part->type = $request->input('type');
            $part->unit = $request->input('unit');
            $part->min_balance = $request->input('min_balance');
            $part->brand = $request->input('brand');
            $part->model = $request->input('model');

            if (isset($_FILES['photo']['name'])) {
                $public_path = public_path();
                $path = $public_path . '/' . "parts";
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
                    $part->photo = $file_name;
                }
            }

            if ($part->save()) {
                $substore = new \App\Models\SubStore;
                $substore->part_id = $part->id;
                $substore->company_id = $part->company_id;
                $substore->qty = 0;
                $substore->save();
            }
            $return['data'] = $part;
            $statusCode = 200;
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
            $part = Part::find($id);

            if ($part) {
                $return['data'] = $part;
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
            $part = Part::find($id);
            $user = User::find($request->user->id);

            if (!$part) {
                $return['errors'] = ['Part not found'];
                return $this->response($return, $statusCode);
            }

            // Custom validation rule to check for existing title and company_id combination, excluding the current part
            $validator = Validator::make($request->all(), [
                'title' => [
                    'required',
                    function ($attribute, $value, $fail) use ($request, $user, $part) {
                        if (Part::where('title', $value)
                                        ->where('company_id', $user->company)
                                        ->where('id', '<>', $part->id)
                                        ->exists()) {
                            $fail('The title has already been taken for this company.');
                        }
                    },
                ],
                'unit' => 'required',
                'type' => 'required',
                'min_balance' => 'required',
                'brand' => 'nullable',
                'model' => 'nullable',
                'photo' => 'nullable',
            ]);

            if ($validator->fails()) {
                $return['errors'] = $validator->errors();
                $statusCode = 422;
                return $this->response($return, $statusCode);
            }

            $part->title = strtoupper($request->input('title'));
            $part->type = $request->input('type');
            $part->unit = $request->input('unit');
            $part->min_balance = $request->input('min_balance');
            $part->brand = $request->input('brand');
            $part->model = $request->input('model');

            if (isset($_FILES['photo']['name'])) {
                $public_path = public_path();
                $path = $public_path . '/' . "parts";
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
                    $part->photo = $file_name;
                }
            }

            if ($part->save()) {
                $return['data'] = $part;
                $statusCode = 200;
                $return['status'] = 'success';
            }

            return $this->response($return, $statusCode);
        } catch (\Throwable $ex) {
            return $this->error(['status' => "error", 'main_error_message' => $ex->getMessage()]);
        }
    }

    public function update_photo(Request $request) {
        try {
            $statusCode = 422;
            $return = [];
            $id = $request->input('id');
            $part = Part::find($id);

            if (isset($_FILES['photo']['name'])) {
                $public_path = public_path();
                $path = $public_path . '/' . "parts";
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
                    $part->photo = $file_name;
                }
            }

            $part->save();
            $return['data'] = $part;
            $statusCode = 200;
            $return['status'] = 'success';

            return $this->response($return, $statusCode);
        } catch (\Throwable $ex) {
            return $this->error(['status' => "error", 'main_error_message' => $ex->getMessage()]);
        }
    }

//    This Function now change as per direction on mr Hafiz sir 23/02/2025 , previous backup function after this function  

    public function required_purchase(Request $request) {
        try {
            $statusCode = 422;
            $return = [];

            // Find the user by ID from the request
            $user = \App\Models\User::find($request->user->id);
            // Get the access information for the user
            $access = \App\Models\SubStoreAccess::where('user_id', $user->id)->first();
            $accessAreas = explode(',', $access->area);

            // Fetch parts based on company and access areas
            $parts = Part::where('company_id', $user->company)
                    ->whereIn('type', $accessAreas)
                    ->get();

            $requiredToPurchase = [];

            // Loop through each part and check if it needs to be requisitioned
            foreach ($parts as $part) {
                $substore = \App\Models\SubStore::where('part_id', $part->id)->first();

                // Ensure substore is not null
                if ($substore && $substore->qty <= $part->min_balance) {
                    $partInfo = [
                        'part_id' => $part->id,
                        'part_name' => $part->title,
                        'title' => $part->title,
                        'id' => $part->id,
                        'unit' => $part->unit,
                        'type' => $part->type,
                        'qty' => '',
                        'remarks' => '',
                        'min_balance' => $part->min_balance,
                        'stock_in_hand' => $substore->qty,
                        'image_source' => url('') . '/parts/' . $part->photo,
                    ];

                    $requiredToPurchase[] = $partInfo;
                }
            }

            $return['parts'] = $requiredToPurchase;
            $return['status'] = 'success';

            $statusCode = 200;
            return $this->response($return, $statusCode);
        } catch (\Exception $ex) {
            return $this->error(['status' => 'error', 'main_error_message' => $ex->getMessage()]);
        }
    }

    public function required_purchase_bkup_23_02_2025_origin(Request $request) {
        try {
            $statusCode = 422;
            $return = [];

            // Find the user by ID from the request
            $user = \App\Models\User::find($request->user->id);
            // Get the access information for the user
            $access = \App\Models\SubStoreAccess::where('user_id', $user->id)->first();
            $accessAreas = explode(',', $access->area);

            // Fetch parts based on company and access areas
            $parts = Part::where('company_id', $user->company)
                    ->whereIn('type', $accessAreas)
                    ->get();

            $requiredToPurchase = [];

            // Loop through each part and check if it needs to be requisitioned
            foreach ($parts as $part) {
                $substore = \App\Models\SubStore::where('part_id', $part->id)->first();

                // Ensure substore is not null
                if ($substore && $substore->qty <= $part->min_balance) {

                    $requisition_item = \App\Models\RequisitionItem::where('part_id', $part->id)->latest()->first();

                    // Check if $requisition_item exists and its purchase_qty is greater than 0 or if $requisition_item does not exist
                    if (!$requisition_item || ($requisition_item && ($requisition_item->status == 'Purchased' || $requisition_item->status == 'Inhoused'))) {
                        $partInfo = [
                            'part_id' => $part->id,
                            'part_name' => $part->title,
                            'title' => $part->title,
                            'id' => $part->id,
                            'unit' => $part->unit,
                            'type' => $part->type,
                            'qty' => '',
                            'remarks' => '',
                            'min_balance' => $part->min_balance,
                            'stock_in_hand' => $substore->qty,
                            'image_source' => url('') . '/parts/' . $part->photo,
                        ];

                        $requiredToPurchase[] = $partInfo;
                    }
                }
            }

            $return['parts'] = $requiredToPurchase;
            $return['status'] = 'success';

            $statusCode = 200;
            return $this->response($return, $statusCode);
        } catch (\Exception $ex) {
            return $this->error(['status' => 'error', 'main_error_message' => $ex->getMessage()]);
        }
    }
}
