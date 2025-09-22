<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\LeftOver;
use App\Models\LeftOverBalance;
use App\Models\LeftOverIssue;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\DB;

class LeftOverController extends Controller {

    public function admin_index(Request $request) {
        try {

            $from_date = $request->input('from_date');
            $to_date = $request->input('to_date');
            $num_of_row = $request->input('num_of_row');
            $filter_items = $request->input('filter_items');
            $buyer_id = $request->input('buyer_id');
            $techpack_id = $request->input('techpack_id');
            $season = $request->input('season');
            $item_type = $request->input('item_type');
            $status = $request->input('status');
            $company_id = $request->input('company_id');
            $query = LeftOver::orderBy('created_at', 'desc');

//            filtering according inputs
            if ($company_id) {
                $query->where('company_id', $company_id);
            }
            if ($from_date && $to_date) {
                $query->whereBetween('created_at', [$from_date, $to_date]);
            }
            if ($techpack_id) {
                $query->where('techpack_id', $techpack_id);
            }
            if ($status) {
                $query->where('status', $status);
            }
            if ($filter_items) {
                $query->whereIn('id', $filter_items);
            }
            if ($buyer_id) {
                $query->where('buyer_id', $buyer_id);
            }
            if ($season) {
                $query->where('season', $season);
            }
            if ($item_type) {
                $query->where('item_type', $item_type);
            }

            $overs = $query->take($num_of_row)->get();

            foreach ($overs as $val) {
                $val->buyer = optional(\App\Models\Buyer::find($val->buyer_id))->name;
                $entry_by = \App\Models\User::find($val->user_id);
                $val->user = $entry_by->full_name;

                if ($val->received_by > 0) {
                    $val->received_by_user = \App\Models\User::find($val->received_by)->full_name;
                } else {
                    $val->received_by_user = 'N/A';
                }
                $val->techpack = optional(\App\Models\Techpack::find($val->techpack_id))->title;
                $val->image_source = url('') . '/left-overs/' . $val->photo;
            }

            $return['data'] = $overs;
            $statusCode = 200;
            $return['status'] = 'success';

            return $this->response($return, $statusCode);
        } catch (\Throwable $ex) {
            return $this->error(['status' => 'error', 'main_error_message' => $ex->getMessage()]);
        }
    }

    public function index(Request $request) {
        try {
            $user = \App\Models\User::find($request->user->id);
            $from_date = $request->input('from_date');
            $to_date = $request->input('to_date');
            $num_of_row = $request->input('num_of_row');
            $filter_items = $request->input('filter_items');
            $buyer_id = $request->input('buyer_id');
            $techpack_id = $request->input('techpack_id');
            $season = $request->input('season');
            $item_type = $request->input('item_type');
            $status = $request->input('status');
            $with_user = $request->input('with_user');
            $with_company = $request->input('with_company');

            $query = LeftOver::orderBy('created_at', 'desc');

            if ($with_user) {
                $query->where('user_id', $user->id);
            }
            if ($with_company) {
                $query->where('company_id', $user->company);
            }



            if ($from_date && $to_date) {
                $query->whereBetween('created_at', [$from_date, $to_date]);
            }
            if ($techpack_id) {
                $query->where('techpack_id', $techpack_id);
            }
            if ($status) {
                $query->where('status', $status);
            }
            if ($filter_items) {
                $query->whereIn('id', $filter_items);
            }
            if ($buyer_id) {
                $query->where('buyer_id', $buyer_id);
            }
            if ($season) {
                $query->where('season', $season);
            }
            if ($item_type) {
                $query->where('item_type', $item_type);
            }

            $overs = $query->take($num_of_row)->get();

            foreach ($overs as $val) {
                $val->buyer = optional(\App\Models\Buyer::find($val->buyer_id))->name;
                $entry_by = \App\Models\User::find($val->user_id);
                $val->user = $entry_by->full_name;

                if ($val->received_by > 0) {
                    $val->received_by_user = \App\Models\User::find($val->received_by)->full_name;
                } else {
                    $val->received_by_user = 'N/A';
                }




                $val->techpack = optional(\App\Models\Techpack::find($val->techpack_id))->title;
                $val->image_source = url('') . '/left-overs/' . $val->photo;
            }

            $return['data'] = $overs;
            $statusCode = 200;

            return $this->response($return, $statusCode);
        } catch (\Throwable $ex) {
            return $this->error(['status' => 'error', 'main_error_message' => $ex->getMessage()]);
        }
    }

    public function store(Request $request) {
        try {
            $validator = Validator::make($request->all(), [
                        'buyer_id' => 'required',
                        'techpack_id' => 'required',
                        'season' => 'required',
                        'title' => 'required',
                        'carton' => 'required',
                        'qty' => 'required',
                        'item_type' => 'required',
                        'reference' => 'nullable',
                        'remarks' => 'nullable',
                        'photo' => 'required|image|mimes:jpeg,png,jpg,gif,svg|max:2048',
            ]);

            if ($validator->fails()) {
                return response()->json(['errors' => $validator->errors()], 422);
            }

            $user = \App\Models\User::findOrFail($request->user->id);

            $entryData = $request->only([
                'buyer_id', 'techpack_id', 'season', 'title', 'carton',
                'qty', 'item_type', 'reference', 'remarks',
            ]);

            $entry = new LeftOver($entryData);
            if ($request->hasFile('photo')) {
                $photo = $request->file('photo');
                $photoName = time() . '_' . $photo->getClientOriginalName();
                $photo->move(public_path('left-overs'), $photoName);
                $entry->photo = $photoName;
            }
            $entry->user_id = $user->id;
            $entry->left_over_id = 0; // You might want to change this to a meaningful value
            $entry->company_id = $user->company;
            $entry->received_by = 0;
            $entry->save();

            $return['data'] = $entry;
            $statusCode = 200;
            $return['status'] = 'success';

            return response()->json($return, $statusCode);
        } catch (\Throwable $ex) {
            return $this->error(['status' => 'error', 'main_error_message' => $ex->getMessage()]);
        }
    }

    public function show(Request $request) {
        try {
            $id = $request->input('id');
            $left_over = LeftOver::find($id);

            if ($left_over) {
                $left_over->buyer = optional(\App\Models\Buyer::find($left_over->buyer_id))->name;
                $left_over->user = \App\Models\User::find($left_over->user_id)->full_name;
                $left_over->techpack = optional(\App\Models\Techpack::find($left_over->techpack_id))->title;
                $left_over->image_source = url('') . '/left-overs/' . $left_over->photo;

                $return['data'] = $left_over;
                $return['status'] = 'success';
                $statusCode = 200;
            } else {
                $return['status'] = 'error';
                $statusCode = 404;
            }

            return $this->response($return, $statusCode);
        } catch (\Throwable $ex) {
            return $this->error(['status' => 'error', 'main_error_message' => $ex->getMessage()]);
        }
    }

    public function update(Request $request) {
        try {
            $id = $request->input('id');

            $validator = Validator::make($request->all(), [
                        'buyer_id' => 'required',
                        'techpack_id' => 'required',
                        'season' => 'required',
                        'title' => 'required',
                        'carton' => 'required',
                        'qty' => 'required',
                        'item_type' => 'required',
                        'reference' => 'nullable',
                        'remarks' => 'nullable',
                        'photo' => 'nullable',
            ]);

            if ($validator->fails()) {
                return response()->json(['errors' => $validator->errors()], 422);
            }

            $update = LeftOver::findOrFail($id);
            $update->fill($request->only([
                        'buyer_id', 'techpack_id', 'season', 'title', 'carton',
                        'qty', 'item_type', 'reference', 'remarks',
            ]));

            if ($request->hasFile('photo')) {
                $photo = $request->file('photo');
                $photoName = time() . '_' . $photo->getClientOriginalName();
                $photo->move(public_path('left-overs'), $photoName);
                $update->photo = $photoName;
            }

            $update->save();
            $return['data'] = $update;
            $return['status'] = 'success';
            $statusCode = 200;

            return response()->json($return, $statusCode);
        } catch (\Throwable $ex) {
            return $this->error(['status' => 'error', 'main_error_message' => $ex->getMessage()]);
        }
    }

    public function receive(Request $request) {
        try {

            $id = $request->input('id');
            $user = \App\Models\User::find($request->user->id);
            $over = LeftOver::find($id);

            if ($over) {
                $find_balance = LeftOverBalance::where('company_id', $over->company_id)
                        ->where('buyer_id', $over->buyer_id)
                        ->where('techpack_id', $over->techpack_id)
                        ->where('season', $over->season)
                        ->where('item_type', $over->item_type)
                        ->first();

                if ($find_balance) {
                    $over->left_over_id = $find_balance->id;
                    $over->status = 'Received';
                    $over->received_by = $user->id;

                    if ($over->save()) {
                        $find_balance->increment('qty', $over->qty);
                        $find_balance->increment('carton', $over->carton);
                        $return['data'] = $over;
                        $return['status'] = 'success';
                        $statusCode = 200;
                    }
                } else {
                    $new_balance = new LeftOverBalance;
                    $new_balance->company_id = $over->company_id;
                    $new_balance->buyer_id = $over->buyer_id;
                    $new_balance->techpack_id = $over->techpack_id;
                    $new_balance->season = $over->season;
                    $new_balance->title = $over->title;
                    $new_balance->carton = $over->carton;
                    $new_balance->qty = $over->qty;
                    $new_balance->item_type = $over->item_type;
                    $new_balance->photo = $over->photo;
                    if ($new_balance->save()) {
                        $over->left_over_id = $new_balance->id;
                        $over->status = 'Received';
                        $over->received_by = $user->id;
                        $over->save();
                        $return['data'] = $over;
                        $return['status'] = 'success';
                        $statusCode = 200;
                    } else {
                        $return['errors']['photo'] = 'Failed to save store record.';
                        $statusCode = 422;
                    }
                }
            }

            return response()->json($return, $statusCode);
        } catch (\Throwable $ex) {
            return $this->error(['status' => 'error', 'main_error_message' => $ex->getMessage()]);
        }
    }

    public function balance(Request $request) {
        try {
            $user = \App\Models\User::find($request->user->id);
            $from_date = $request->input('from_date');
            $to_date = $request->input('to_date');
            $num_of_row = $request->input('num_of_row');
            $filter_items = $request->input('filter_items');
            $buyer_id = $request->input('buyer_id');
            $techpack_id = $request->input('techpack_id');
            $season = $request->input('season');
            $item_type = $request->input('item_type');
            $status = $request->input('status');
            $query = LeftOverBalance::where('company_id', $user->company)->orderBy('created_at', 'desc');

            if ($from_date && $to_date) {
                $query->whereBetween('created_at', [$from_date, $to_date]);
            }
            if ($techpack_id) {
                $query->where('techpack_id', $techpack_id);
            }
            if ($status) {
                $query->where('status', $status);
            }
            if ($filter_items) {
                $query->whereIn('id', $filter_items);
            }
            if ($buyer_id) {
                $query->where('buyer_id', $buyer_id);
            }
            if ($season) {
                $query->where('season', $season);
            }
            if ($item_type) {
                $query->where('item_type', $item_type);
            }

            $overs = $query->take($num_of_row)->get();

            foreach ($overs as $val) {
                $val->buyer = optional(\App\Models\Buyer::find($val->buyer_id))->name;
                $val->techpack = optional(\App\Models\Techpack::find($val->techpack_id))->title;
                $val->image_source = url('') . '/left-overs/' . $val->photo;
            }

            $return['data'] = $overs;
            $statusCode = 200;

            return $this->response($return, $statusCode);
        } catch (\Throwable $ex) {
            return $this->error(['status' => 'error', 'main_error_message' => $ex->getMessage()]);
        }
    }

    public function admin_balance(Request $request) {
        try {

            $from_date = $request->input('from_date');
            $to_date = $request->input('to_date');
            $num_of_row = $request->input('num_of_row');
            $filter_items = $request->input('filter_items');
            $buyer_id = $request->input('buyer_id');
            $techpack_id = $request->input('techpack_id');
            $season = $request->input('season');
            $item_type = $request->input('item_type');
            $status = $request->input('status');
            $company_id = $request->input('company_id');

//            query builder instance
            $query = LeftOverBalance::orderBy('created_at', 'desc');

            if ($company_id) {
                $query->where('company_id', $company_id);
            }

            if ($from_date && $to_date) {
                $query->whereBetween('created_at', [$from_date, $to_date]);
            }
            if ($techpack_id) {
                $query->where('techpack_id', $techpack_id);
            }
            if ($status) {
                $query->where('status', $status);
            }
            if ($filter_items) {
                $query->whereIn('id', $filter_items);
            }
            if ($buyer_id) {
                $query->where('buyer_id', $buyer_id);
            }
            if ($season) {
                $query->where('season', $season);
            }
            if ($item_type) {
                $query->where('item_type', $item_type);
            }

            $overs = $query->take($num_of_row)->get();

            foreach ($overs as $val) {
                $val->buyer = optional(\App\Models\Buyer::find($val->buyer_id))->name;
                $val->techpack = optional(\App\Models\Techpack::find($val->techpack_id))->title;
                $val->image_source = url('') . '/left-overs/' . $val->photo;
                $val->company_name = optional(\App\Models\Company::find($val->company_id))->title;
            }

            $return['data'] = $overs;
            $return['status'] = 'success';

            $statusCode = 200;

            return $this->response($return, $statusCode);
        } catch (\Throwable $ex) {
            return $this->error(['status' => 'error', 'main_error_message' => $ex->getMessage()]);
        }
    }

    public function balance_details(Request $request) {
        try {
            $id = $request->input('id');
            $balance = LeftOverBalance::find($id);
            if ($balance) {
                $balance->buyer = optional(\App\Models\Buyer::find($balance->buyer_id))->name;
                $balance->techpack = optional(\App\Models\Techpack::find($balance->techpack_id))->title;
                $balance->image_source = url('') . '/left-overs/' . $balance->photo;
                $balance->company_name = optional(\App\Models\Company::find($balance->company_id))->title;
                $issues = LeftOverIssue::where('left_over_id', $balance->id)->orderBy('created_at', 'desc')->get();
                $balance->total_issue_qty = $issues->sum('qty');
                $balance->total_issue_carton = $issues->sum('carton');

                foreach ($issues as $issue) {
                    $issue->issue_by_user = optional(\App\Models\User::find($issue->user_id))->full_name;
                    $issue->challan_file = url('') . '/challan-copies/' . $issue->challan_copy;
                }

                $balance->issues = $issues;

                $receives = LeftOver::where('left_over_id', $balance->id)->where('status', 'Received')->orderBy('created_at', 'desc')->get();
                $balance->total_receive_qty = $receives->sum('qty');
                $balance->total_receive_carton = $receives->sum('carton');

                foreach ($receives as $receive) {
                    $receive->received_by_user = optional(\App\Models\User::find($receive->received_by))->full_name;
                    $receive->issue_by_user = optional(\App\Models\User::find($receive->user_id))->full_name;
                }



                $balance->receives = $receives;

                $return['data'] = $balance;
                $return['status'] = 'success';
                $statusCode = 200;
            } else {
                $return['status'] = 'error';
                $statusCode = 404;
            }

            return $this->response($return, $statusCode);
        } catch (\Throwable $ex) {
            return $this->error(['status' => 'error', 'main_error_message' => $ex->getMessage()]);
        }
    }

    public function issue_item(Request $request) {
        try {
            $statusCode = 422;
            $return = [];
            $user_id = $request->user->id;
            $validator = Validator::make($request->all(), [
                        'id' => 'required',
                        'issue_type' => 'required',
                        'reference' => 'required',
                        'issue_to_company_id' => $request->input('issue_type') === 'Sister-Factory' ? 'required' : 'nullable',
                        'buyer_id' => 'required',
                        'techpack_id' => 'required',
                        'title' => 'required',
                        'remarks' => 'nullable',
                        'carton' => 'required',
                        'qty' => 'required',
                        'photo' => 'required',
                        'challan_copy' => 'required',
                        'issue_qty' => 'required',
                        'issue_carton' => 'required',
                        'item_type' => 'required',
                        'season' => 'required',
            ]);

            if ($validator->fails()) {
                return response()->json(['errors' => $validator->errors()], 422);
            }

            $total_qty = $request->input('qty');
            $issue_qty = $request->input('issue_qty');
            $total_carton = $request->input('carton');
            $issue_carton = $request->input('issue_carton');
            $leftover_id = $request->input('id');
            if ($issue_qty <= $total_qty && $issue_carton <= $total_carton) {
                $issue = new LeftOverIssue;
                $issue->user_id = $user_id;
                $issue->left_over_id = $leftover_id;
                $issue->issue_type = $request->input('issue_type');
                $issue->reference = $request->input('reference');
                $issue->issue_to_company_id = $request->input('issue_to_company_id');
                $issue->buyer_id = $request->input('buyer_id');
                $issue->techpack_id = $request->input('techpack_id');
                $issue->title = $request->input('title');
                $issue->remarks = $request->input('remarks');
                $issue->carton = $issue_carton;
                $issue->qty = $issue_qty;
                $issue->photo = $request->input('photo');
                $issue->item_type = $request->input('item_type');
                $issue->season = $request->input('season');
                if ($request->hasFile('challan_copy')) {
                    $challanCopy = $request->file('challan_copy');
                    $challanCopyName = time() . '_' . $challanCopy->getClientOriginalName();
                    $challanCopy->move(public_path('challan-copies'), $challanCopyName);
                    $issue->challan_copy = $challanCopyName;
                }
                if ($issue->save()) {
                    $balance = LeftOverBalance::find($issue->left_over_id);

                    if ($issue->issue_type === "Sister-Factory") {
                        $over = new LeftOver;
                        $over->buyer_id = $issue->buyer_id;
                        $over->techpack_id = $issue->techpack_id;
                        $over->season = $issue->season;
                        $over->title = $issue->title;
                        $over->carton = $issue->carton;
                        $over->qty = $issue->qty;
                        $over->item_type = $issue->item_type;
                        $over->reference = $issue->reference;
                        $over->remarks = $issue->remarks;
                        $over->photo = $issue->photo;
                        $over->user_id = $user_id;
                        $over->left_over_id = 0; // You might want to change this to a meaningful value
                        $over->company_id = $issue->issue_to_company_id;
                        $over->received_by = 0;
                        if ($over->save()) {
                            $balance->decrement('qty', $issue->qty);
                            $balance->decrement('carton', $issue->carton);
                        }
                    } else {
                        $balance->decrement('qty', $issue->qty);
                        $balance->decrement('carton', $issue->carton);
                    }
                    $return['data'] = $issue;
                    $statusCode = 200;
                    $return['status'] = 'success';
                }
            } else {
                $return['errors']['issue_qty'] = 'Trying to insert greater than stock qty';
            }

            return response()->json($return, $statusCode);
        } catch (\Throwable $ex) {
            return $this->error(['status' => 'error', 'main_error_message' => $ex->getMessage()]);
        }
    }

}
