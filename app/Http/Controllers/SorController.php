<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Sor;
use App\Models\SorItem;
use Illuminate\Support\Facades\Validator;
use App\Models\Team;
use App\Models\SampleStore;
use App\Models\SampleBalance;
use App\Models\SampleStoreActivity;

class SorController extends Controller {

    public function admin_index(Request $request) {
        try {
            $statusCode = 422;
            $return = [];
            $from_date = $request->input('from_date');
            $to_date = $request->input('to_date');
            $status = $request->input('status');
            $num_of_row = $request->input('num_of_row');
            $filter_items = $request->input('filter_items');
            $buyer_id = $request->input('buyer_id');
            $view = $request->input('view');
            $department = $request->input('department');
            $designation = $request->input('designation');

            $query = Sor::orderBy('created_at', 'desc');

            if ($department && $designation) {
                if ($department == "Merchandising") {
                    if ($view) {
                        if ($view === 'self') {
                            $query->where('user_id', $user_id);
                        } else if ($view === 'team') {
                            $find_user_team = Team::whereRaw("FIND_IN_SET('$user_id', employees)")->first();
                            $team_users = explode(',', $find_user_team->employees);
                            $query->whereIn('user_id', $team_users);
                        }
                    }
                }
            } else {
                $query->orderBy('created_at', 'desc');
            }




            if ($from_date && $to_date) {
                $query->whereBetween('issued_date', [$from_date, $to_date]);
            }
            if ($filter_items) {
                $query->whereIn('id', $filter_items);
            }
            if ($buyer_id) {
                $query->where('buyer_id', $buyer_id);
            }
            if ($status) {
                $query->where('status', $status);
            }
            // Limit the result to "num_of_row" records
            $sors = $query->take($num_of_row)->get();
            if ($sors) {
                foreach ($sors as $val) {
                    $buyer = \App\Models\Buyer::where('id', $val->buyer_id)->first();
                    $user = \App\Models\User::where('id', $val->user_id)->first();
                    $val->buyer = $buyer->name;
                    $val->user = $user->full_name;
                    $action = \App\Models\User::where('id', $val->action_by)->first();
                    $val->action_by_name = $action->full_name;

                    $techpack = \App\Models\Techpack::where('id', $val->techpack_id)->first();
                    $val->techpack = $techpack->title;

                    $colorArray = explode(',', $val->colors);
                    $colors = \App\Models\Color::whereIn('id', $colorArray)->get();
                    $val->colorList = $colors;

                    $sizeArray = explode(',', $val->sizes);
                    $sizes = \App\Models\Size::whereIn('id', $sizeArray)->get();
                    $val->sizeList = $sizes;

                    $sample_type = \App\Models\SampleType::where('id', $val->sample_type)->first();
                    $val->sample_type_name = $sample_type->title;
                    $val->image_source = url('') . '/sors/' . $val->photo;
                }
                $return['data'] = $sors;
                $statusCode = 200;
            } else {
                $return['status'] = 'error';
            }
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
            $status = $request->input('status');
            $num_of_row = $request->input('num_of_row');
            $filter_items = $request->input('filter_items');
            $buyer_id = $request->input('buyer_id');
            $view = $request->input('view');
            $department = $request->input('department');
            $designation = $request->input('designation');

            $allData = Sor::orderBy('created_at', 'desc')->get();

            $query = Sor::orderBy('created_at', 'desc');

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



            if ($from_date && $to_date) {
                $query->whereBetween('issued_date', [$from_date, $to_date]);
            }
            if ($filter_items) {
                $query->whereIn('id', $filter_items);
            }

            if ($buyer_id) {
                $query->where('buyer_id', $buyer_id);
            }

            if ($status) {
                $query->where('status', $status);
            }

            // Limit the result to "num_of_row" records
            $sors = $query->take($num_of_row)->get();
            if ($sors) {
                foreach ($sors as $val) {
                    $buyer = \App\Models\Buyer::where('id', $val->buyer_id)->first();
                    $user = \App\Models\User::where('id', $val->user_id)->first();
                    $val->buyer = $buyer->name;
                    $val->user = $user->full_name;
                    $action = \App\Models\User::where('id', $val->action_by)->first();
                    $val->action_by_name = $action->full_name;

                    $techpack = \App\Models\Techpack::where('id', $val->techpack_id)->first();
                    $val->techpack = $techpack->title;

                    $colorArray = explode(',', $val->colors);
                    $colors = \App\Models\Color::whereIn('id', $colorArray)->get();
                    $val->colorList = $colors;

                    $sizeArray = explode(',', $val->sizes);
                    $sizes = \App\Models\Size::whereIn('id', $sizeArray)->get();
                    $val->sizeList = $sizes;
                    $sample_type = \App\Models\SampleType::where('id', $val->sample_type)->first();
                    $val->sample_type_name = $sample_type->title;
                    $val->image_source = url('') . '/sors/' . $val->photo;
                }
                $return['data'] = $sors;
                $return['allData'] = $allData;

                $statusCode = 200;
            } else {
                $return['status'] = 'error';
            }
            return $this->response($return, $statusCode);
        } catch (\Throwable $ex) {
            return $this->error(['status' => 'error', 'main_error_message' => $ex->getMessage()]);
        }
    }

    public function index_for_sample_section(Request $request) {
        try {
            $statusCode = 422;
            $return = [];
            $user_id = $request->user->id;
            $user = \App\Models\User::where('id', $user_id)->first();
            $from_date = $request->input('from_date');
            $to_date = $request->input('to_date');
            $status = $request->input('status');
            $num_of_row = $request->input('num_of_row');
            $filter_items = $request->input('filter_items');
            $buyer_id = $request->input('buyer_id');
            $allData = Sor::where('status', '!=', 'Pending')->orderBy('created_at', 'desc')->get();
            // Build the query with filters and ordering

            $query = Sor::where('status', '!=', 'Pending')->orderBy('created_at', 'desc');

            // Apply the date range filter if both "from_date" and "to_date" are provided
            if ($from_date && $to_date) {
                $query->whereBetween('issued_date', [$from_date, $to_date]);
            }
            if ($filter_items) {
                $query->whereIn('id', $filter_items);
            }

            // Apply the status filter if "status" is provided
            if ($status) {
                $query->where('status', $status);
            }
            if ($buyer_id) {
                $query->where('buyer_id', $buyer_id);
            }

            // Limit the result to "num_of_row" records
            $sors = $query->take($num_of_row)->get();

            if ($sors) {
                foreach ($sors as $val) {
                    $buyer = \App\Models\Buyer::where('id', $val->buyer_id)->first();
                    $user = \App\Models\User::where('id', $val->user_id)->first();
                    $val->buyer = $buyer->name;
                    $val->user = $user->full_name;
                    $action = \App\Models\User::where('id', $val->action_by)->first();
                    $val->action_by_name = $action->full_name;

                    $techpack = \App\Models\Techpack::where('id', $val->techpack_id)->first();
                    $val->techpack = $techpack->title;

                    $colorArray = explode(',', $val->colors);
                    $colors = \App\Models\Color::whereIn('id', $colorArray)->get();
                    $val->colorList = $colors;

                    $sizeArray = explode(',', $val->sizes);
                    $sizes = \App\Models\Size::whereIn('id', $sizeArray)->get();
                    $val->sizeList = $sizes;
                    $sample_type = \App\Models\SampleType::where('id', $val->sample_type)->first();
                    $val->sample_type_name = $sample_type->title;
                    $val->image_source = url('') . '/sors/' . $val->photo;
                }
                $return['data'] = $sors;
                $return['allData'] = $allData;
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
            $user_id = $request->user->id;
            $sor_items = json_decode($request->input('sor_items'));

            $validator = Validator::make($request->all(), [
                        'techpack_id' => 'required',
                        'sample_type' => 'required',
                        'qty' => 'required',
                        'photo' => 'required',
            ]);
            if ($validator->fails()) {
                return response()->json([
                            'errors' => $validator->errors()
                                ], 422);
            }



            $sor = new Sor;
            $techpack_id = $request->input('techpack_id');
            $techpack = \App\Models\Techpack::where('id', $techpack_id)->first();
            $sor->techpack_id = $techpack->id;
            $sor->buyer_id = $techpack->buyer_id;
            $sor->sample_type = $request->input('sample_type');
            $sor->season = $techpack->season;
            $sor->qty = $request->input('qty');
            $sor->sizes = $request->input('sizes');
            $sor->colors = $request->input('colors');
            $sor->issued_date = $request->input('issued_date');
            $sor->delivery_date = $request->input('delivery_date');
            $sor->remarks = $request->input('remarks');
            $sor->operations = $request->input('operations');
            $sor->user_id = $user_id;
            $sor->action_by = $user_id;
            $sor->status = "Pending";

            if ($request->hasFile('photo')) {
                $photo = $request->file('photo');
                $photoName = time() . '_' . $photo->getClientOriginalName();
                $photo->move(public_path('sors'), $photoName);
                $sor->photo = $photoName;
            }
            $sor->save();

            if (request()->hasFile('attatchments')) {
                $files = request()->file('attatchments');
                foreach ($files as $file) {
                    $upload = new \App\Models\SorFile();
                    $upload->sor_id = $sor->id;
                    $public_path = public_path();
                    $path = $public_path . '/' . "sors";
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
            foreach ($sor_items as $val) {
                $balance = SampleBalance::where('sample_store_id', $val->id)->first();
                if ($val->total <= $balance->qty) {
                    $item = new SorItem;
                    $item->sor_id = $sor->id;
                    $item->sample_store_id = $val->id;
                    $item->description = $val->description;
                    $item->color = $val->color;
                    $item->unit = $val->unit;
                    $item->size = $val->size;
                    $item->per_pc_cons = $val->per_pc_cons;
                    $item->total = $val->total;
                    if ($item->save()) {
                        $balance->decrement('qty', $item->total);
                        $activity = new SampleStoreActivity;
                        $activity->user_id = $user_id;
                        $activity->sample_store_id = $item->sample_store_id;
                        $activity->type = 'Issue';
                        $activity->qty = $item->total;
                        $activity->sor_id = $sor->id;
                        $activity->save();
                    }
                } else {
                    $return['errors']['total'] = 'Total Amount Must be Less than Stock';
                }
            }
            $return['data'] = $sor;
            $statusCode = 200;
            $return['status'] = 'success';

            return response()->json($return, $statusCode);
        } catch (\Throwable $ex) {
            return $this->error(['status' => 'error', 'main_error_message' => $ex->getMessage()]);
        }
    }

    public function admin_show(Request $request) {
        try {
            $statusCode = 422;
            $return = [];
            $id = $request->input('id');
            $sor = Sor::find($id);
            if ($sor) {
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
                $return['data'] = $sor;
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

    public function show(Request $request) {
        try {
            $statusCode = 422;
            $return = [];

            $id = $request->input('id');
            $sor = Sor::find($id);
            if ($sor) {
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
                
                
                
                $return['data'] = $sor;
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
            $user_id = $request->user->id;
            $sor_items = json_decode($request->input('sor_items'));

            $validator = Validator::make($request->all(), [
                        'techpack_id' => 'required',
                        'sample_type' => 'required',
                        'qty' => 'required',
                        'photo' => 'required',
            ]);

            if ($validator->fails()) {
                return response()->json([
                            'errors' => $validator->errors()
                                ], 422);
            }

            $sor = Sor::find($id);

            if (!$sor) {
                return response()->json([
                            'error' => 'SOR not found'
                                ], 404);
            }

            $techpack_id = $request->input('techpack_id');
            $techpack = \App\Models\Techpack::where('id', $techpack_id)->first();
            $sor->techpack_id = $techpack->id;
            $sor->buyer_id = $techpack->buyer_id;
            $sor->sample_type = $request->input('sample_type');
            $sor->season = $techpack->season;
            $sor->qty = $request->input('qty');
            $sor->sizes = $request->input('sizes');
            $sor->colors = $request->input('colors');
            $sor->issued_date = $request->input('issued_date');
            $sor->delivery_date = $request->input('delivery_date');
            $sor->remarks = $request->input('remarks');
            $sor->operations = $request->input('operations');
            $sor->user_id = $user_id;
            $sor->action_by = $user_id;
            $sor->status = "Pending";

            if ($request->hasFile('photo')) {
                $photo = $request->file('photo');
                $photoName = time() . '_' . $photo->getClientOriginalName();
                $photo->move(public_path('sors'), $photoName);
                $sor->photo = $photoName;
            }
            $sor->save();

            if (request()->hasFile('attatchments')) {
                $files = request()->file('attatchments');
                foreach ($files as $file) {
                    $upload = new \App\Models\SorFile();
                    $upload->sor_id = $sor->id;
                    $public_path = public_path();
                    $path = $public_path . '/' . "sors";

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

            // Delete existing SorItems for this SOR
            SorItem::where('sor_id', $sor->id)->delete();

            foreach ($sor_items as $val) {
                $item = new SorItem;
                $item->sor_id = $sor->id;
                $item->item_id = $val->item_id;
                $item->description = $val->description;
                $item->color = $val->color;
                $item->size = $val->size;
                $item->unit = $val->unit;
                $item->per_pc_cons = $val->per_pc_cons;
                $item->qty = $val->qty;
                $item->save();
            }

            $return['data'] = $sor;
            $statusCode = 200;
            $return['status'] = 'success';

            return response()->json($return, $statusCode);
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
            $remarks = $request->input('remarks');
            $user_id = $request->user->id;
            $sor = Sor::find($id);
            $sor->status = $status;
            $sor->remarks = $remarks;
            $sor->action_by = $user_id;
            $sor->save();

            if (request()->hasFile('attatchments')) {
                $files = request()->file('attatchments');
                foreach ($files as $file) {
                    $upload = new \App\Models\SorFile();
                    $upload->sor_id = $sor->id;
                    $public_path = public_path();
                    $path = $public_path . '/' . "sors";

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
            $return['data'] = $sor;

            $statusCode = 200;
            $return['status'] = 'success';

            return response()->json($return, $statusCode);
        } catch (\Throwable $ex) {
            return $this->error(['status' => 'error', 'main_error_message' => $ex->getMessage()]);
        }
    }

    public function toggleitemstatus(Request $request) {
        try {
            $statusCode = 422;
            $return = [];
            $id = $request->input('id');
            $status = $request->input('status');
            $sor_item = SorItem::find($id);
            $sor_item->status = $status;
            $sor_item->save();
            $return['data'] = $sor_item;
            $statusCode = 200;
            $return['status'] = 'success';
            return response()->json($return, $statusCode);
        } catch (\Throwable $ex) {
            return $this->error(['status' => 'error', 'main_error_message' => $ex->getMessage()]);
        }
    }

    public function destroy(Request $request) {
        try {
            $statusCode = 422;
            $return = [];

            // input_variable
            $id = $request->input('id');
            $sor = Sor::find($id);

            if ($sor) {
                $sor_items = SorItem::where('sor_id', $sor->id)->get();
                foreach ($sor_items as $val) {
                    $balance = SampleBalance::where('sample_store_id', $val->sample_store_id)->first();
                    if ($balance) {
                        $balance->increment('qty', $val->total);
                    }
                }
            }
            $delete = $sor->delete();
            if ($delete) {
                \App\Models\SorFile::where('sor_id', $sor->id)->delete();
                SorItem::where('sor_id', $sor->id)->delete();
                SampleStoreActivity::where('sor_id', $sor->id)->delete();
            }
            $statusCode = 200;
            $return['status'] = 'success';
            return $this->response($return, $statusCode);
        } catch (\Throwable $ex) {
            return $this->error(['status' => "error", 'main_error_message' => $ex->getMessage()]);
        }
    }

}
