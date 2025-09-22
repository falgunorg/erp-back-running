<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Design;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\DB;
use App\Models\DesignItem;
use App\Models\Team;

class DesignController extends Controller {

    public function admin_index(Request $request) {
        try {
            $statusCode = 422;
            $return = [];
            $from_date = $request->input('from_date');
            $to_date = $request->input('to_date');
            $status = $request->input('status');
            $buyer_id = $request->input('buyer_id');
            $num_of_row = $request->input('num_of_row');
            $filter_items = $request->input('filter_items');

            $query = Design::orderBy('created_at', 'desc');
            // Apply filters
            if ($status) {
                $query->where('status', $status);
            }
            if ($buyer_id) {
                $query->whereRaw("FIND_IN_SET(?, buyers)", [$buyer_id]);
            }
            if ($filter_items) {
                $query->whereIn('id', $filter_items);
            }
            // Apply date range filter if both "from_date" and "to_date" are provided
            if ($from_date && $to_date) {
                $query->whereBetween('created_at', [$from_date, $to_date]);
            }
            $designs = $query->take($num_of_row)->get();
            foreach ($designs as $val) {
                $buyerArray = explode(',', $val->buyers);
                $buyers = \App\Models\Buyer::whereIn('id', $buyerArray)->get();
                $val->buyersLists = $buyers;
                $user = \App\Models\User::where('id', $val->user_id)->first();
                $val->user_name = $user->full_name;
                $val->image_source = url('') . '/designs/' . $val->photo;
            }
            $return['data'] = $designs;
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
            $from_date = $request->input('from_date');
            $to_date = $request->input('to_date');
            $status = $request->input('status');
            $buyer_id = $request->input('buyer_id');
            $num_of_row = $request->input('num_of_row');
            $filter_items = $request->input('filter_items');
            $user_id = $request->user->id;

            $find_user_team = Team::whereRaw("FIND_IN_SET('$user_id', employees)")->first();
            $return['Team'] = $find_user_team;
            $team_users = explode(',', $find_user_team->employees);

//all Designs 
            $design_all = Design::whereIn('user_id', $team_users)->get();
// Query builder instance
            $query = Design::whereIn('user_id', $team_users)
                    ->orderBy('created_at', 'desc');
            // Apply filters
            if ($status) {
                $query->where('status', $status);
            }
            if ($buyer_id) {
                $query->whereRaw("FIND_IN_SET(?, buyers)", [$buyer_id]);
            }
            if ($filter_items) {
                $query->whereIn('id', $filter_items);
            }
            // Apply date range filter if both "from_date" and "to_date" are provided
            if ($from_date && $to_date) {
                $query->whereBetween('created_at', [$from_date, $to_date]);
            }
            $designs = $query->take($num_of_row)->get();
            foreach ($designs as $val) {
                $buyerArray = explode(',', $val->buyers);
                $buyers = \App\Models\Buyer::whereIn('id', $buyerArray)->get();
                $val->buyersLists = $buyers;
                $user = \App\Models\User::where('id', $val->user_id)->first();
                $val->user_name = $user->full_name;
                $val->image_source = url('') . '/designs/' . $val->photo;
            }
            $return['data'] = $designs;
            $return['all_designs'] = $design_all;
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
            $design_items = json_decode($request->input('design_items'));

            $validator = Validator::make($request->all(), [
                        'title' => 'required',
                        'design_type' => 'required',
                        'buyers' => 'required',
            ]);

            if ($validator->fails()) {
                return response()->json([
                            'errors' => $validator->errors()
                                ], 422);
            }

            $design = new Design([
                'user_id' => $request->user->id,
                'title' => $request->input('title'),
                'design_type' => $request->input('design_type'),
                'buyers' => $request->input('buyers'),
                'description' => $request->input('description'),
                'status' => "Pending",
            ]);

            if ($request->hasFile('photo')) {
                $photo = $request->file('photo');
                $photoName = time() . '_' . $photo->getClientOriginalName();
                $photo->move(public_path('designs'), $photoName);
                $design->photo = $photoName;
            }

            if ($design->save()) {
                foreach ($design_items as $val) {
                    $item = new DesignItem;
                    $item->design_id = $design->id;
                    $item->item_id = $val->item_id;
                    $item->description = $val->description;
                    $item->color = $val->color;
                    $item->unit = $val->unit;
                    $item->size = $val->size;
                    $item->qty = $val->qty;
                    $item->rate = $val->rate;
                    $item->total = $val->total;
                    $item->save();
                }
            }

            if (request()->hasFile('attatchments')) {
                $files = request()->file('attatchments');
                foreach ($files as $file) {
                    $upload = new \App\Models\DesignFile();
                    $upload->design_id = $design->id;
                    $public_path = public_path();
                    $path = $public_path . '/' . "designs";
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
            $total_amount = DesignItem::where('design_id', $design->id)->sum('total');
            $design->total = $total_amount;
            $design->save();
            $statusCode = 200;
            $return['data'] = $design;
            $return['status'] = 'success';
            return $this->response($return, $statusCode);
        } catch (\Throwable $ex) {
            return $this->error(['status' => "error", 'main_error_message' => $ex->getMessage()]);
        }
    }

    public function admin_design_approve(Request $request) {
        try {
            $statusCode = 422;
            $return = [];
            $id = $request->input('id');
            $design = Design::find($id);
            if ($design) {
                $design->status = 'Approved';
                $design->save();
                $return['data'] = $design;
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
            $design = Design::find($id);
            if ($design) {
                $user = \App\Models\User::where('id', $design->user_id)->first();
                $design->user_name = $user->full_name;
                $design->image_source = url('') . '/designs/' . $design->photo;
                $buyerArray = explode(',', $design->buyers);
                $buyers = \App\Models\Buyer::whereIn('id', $buyerArray)->get();
                $design->buyersLists = $buyers;
                $attachments = \App\Models\DesignFile::where('design_id', $design->id)->get();
                foreach ($attachments as $val) {
                    $val->file_source = url('') . '/designs/' . $val->filename;
                }
                $design->attachments = $attachments;
                $design_items = DesignItem::where('design_id', $design->id)->get();
                foreach ($design_items as $val) {
                    $item = \App\Models\Item::where('id', $val->item_id)->first();
                    $val->title = $item->title;
                }
                $design->design_items = $design_items;
                $return['data'] = $design;
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
            $design = Design::findOrFail($id);
            $design_items = json_decode($request->input('design_items'));

            $validator = Validator::make($request->all(), [
                        'title' => 'required',
                        'design_type' => 'required',
                        'buyers' => 'required',
            ]);

            if ($validator->fails()) {
                return response()->json([
                            'errors' => $validator->errors()
                                ], 422);
            }

            $design->title = $request->input('title');
            $design->design_type = $request->input('design_type');
            $design->buyers = $request->input('buyers');
            $design->description = $request->input('description');
            $design->status = "Pending";

            if ($request->hasFile('photo')) {
                $photo = $request->file('photo');
                $photoName = time() . '_' . $photo->getClientOriginalName();
                $photo->move(public_path('designs'), $photoName);
                $design->photo = $photoName;
            }

            if ($design->save()) {
                DesignItem::where('design_id', $design->id)->delete();
                foreach ($design_items as $val) {
                    $item = new DesignItem;
                    $item->design_id = $design->id;
                    $item->item_id = $val->item_id;
                    $item->description = $val->description;
                    $item->color = $val->color;
                    $item->unit = $val->unit;
                    $item->size = $val->size;
                    $item->qty = $val->qty;
                    $item->rate = $val->rate;
                    $item->total = $val->total;
                    $item->save();
                }
            }


            if (request()->hasFile('attatchments')) {
                $files = request()->file('attatchments');
                foreach ($files as $file) {
                    $upload = new \App\Models\DesignFile();
                    $upload->design_id = $design->id;
                    $public_path = public_path();
                    $path = $public_path . '/' . "designs";
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
            $total_amount = DesignItem::where('design_id', $design->id)->sum('total');
            $design->total = $total_amount;
            $design->save();
            $statusCode = 200;
            $return['data'] = $design;
            $return['status'] = 'success';
            return $this->response($return, $statusCode);
        } catch (\Throwable $ex) {
            return $this->error(['status' => "error", 'main_error_message' => $ex->getMessage()]);
        }
    }

}
