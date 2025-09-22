<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Item;
use App\Models\ItemType;
use Illuminate\Support\Facades\Validator;

class ItemController extends Controller {

    public function item_type_index(Request $request) {
        try {
            $statusCode = 422;
            $return = [];
            $item_types = ItemType::all();
            if ($item_types) {
                $return['data'] = $item_types;
                $statusCode = 200;
            } else {
                $return['status'] = 'error';
            }
            return $this->response($return, $statusCode);
        } catch (\Throwable $ex) {
            return $this->error(['status' => 'error', 'main_error_message' => $ex->getMessage()]);
        }
    }

    public function item_type_store(Request $request) {
        try {
            $statusCode = 422;
            $return = [];
            $validator = Validator::make($request->all(), [
                'title' => 'required|unique:item_types',
            ]);
            if ($validator->fails()) {
                $return['errors'] = $validator->errors();
                $statusCode = 422;
            } else {
                $item_type = new ItemType;
                $item_type->title = $request->input('title');
                $item_type->save();
                $return['data'] = $item_type;
                $statusCode = 200;
                $return['status'] = 'success';
            }
            return $this->response($return, $statusCode);
        } catch (\Throwable $ex) {
            return $this->error(['status' => "error", 'main_error_message' => $ex->getMessage()]);
        }
    }

    public function item_type_show(Request $request) {
        try {
            $statusCode = 422;
            $return = [];

            $id = $request->input('id');
            $item_type = ItemType::find($id);

            if ($item_type) {
                $return['data'] = $item_type;
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

    public function item_type_update(Request $request) {
        try {
            $statusCode = 422;
            $return = [];
            $id = $request->input('id');
            $item_type = ItemType::find($id);

            $validator = Validator::make($request->all(), [
                'title' => 'required|unique:item_types,title,' . $id,
            ]);
            if ($validator->fails()) {
                $return['errors'] = $validator->errors();
                $statusCode = 422;
            } else {
                $item_type->title = $request->input('title');
                $item_type->save();
                $return['data'] = $item_type;
                $statusCode = 200;
                $return['status'] = 'success';
            }


            return $this->response($return, $statusCode);
        } catch (\Throwable $ex) {
            return $this->error(['status' => "error", 'main_error_message' => $ex->getMessage()]);
        }
    }

    public function item_type_destroy(Request $request) {
        try {
            $statusCode = 422;
            $return = [];
            $id = $request->input('id');
            $item_type = ItemType::find($id);

            if ($item_type->items()->count() > 0) {
                $return['errors'] = $validator->errors();
            }
            // Proceed with deletion if no associated documents
            $cabinet->delete();

            return $this->response($return, $statusCode);
        } catch (\Throwable $ex) {
            return $this->error(['status' => "error", 'main_error_message' => $ex->getMessage()]);
        }
    }

    public function index(Request $request) {
        try {
            $statusCode = 422;
            $return = [];
            $items = Item::orderBy('created_at', 'desc')->get();
            if ($items) {
                $return['data'] = $items;
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
            $validator = Validator::make($request->all(), [
                'title' => 'required|unique:items',
                'unit' => 'required',
            ]);
            if ($validator->fails()) {
                $return['errors'] = $validator->errors();
                $statusCode = 422;
            } else {
                $item = new Item;
                $item->title = $request->input('title');
                $item->user_id = $user_id;
                $item->unit = $request->input('unit');
                $item->save();
                $return['data'] = $item;
                $statusCode = 200;
                $return['status'] = 'success';
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
            $item = Item::find($id);

            if ($item) {
                $return['data'] = $item;
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
            $item = Item::find($id);

            $validator = Validator::make($request->all(), [
                'title' => 'required|unique:items,title,' . $id,
                'unit' => 'required',
            ]);
            if ($validator->fails()) {
                $return['errors'] = $validator->errors();
                $statusCode = 422;
            } else {
                $item->title = $request->input('title');
                $item->unit = $request->input('unit');
                $item->save();
                $return['data'] = $item;
                $statusCode = 200;
                $return['status'] = 'success';
            }


            return $this->response($return, $statusCode);
        } catch (\Throwable $ex) {
            return $this->error(['status' => "error", 'main_error_message' => $ex->getMessage()]);
        }
    }
}
