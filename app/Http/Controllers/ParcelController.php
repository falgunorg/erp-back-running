<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use App\Models\User;
use App\Models\Company;
use App\Models\Parcel;

class ParcelController extends Controller {

    public function index(Request $request) {
        try {
            $statusCode = 422;
            $return = [];
            $user = User::find($request->user->id);
            $user_id = $user->id;
            $tracking_number = $request->input('tracking_number');

            $query = Parcel::orderBy('created_at', 'desc');

            if ($tracking_number) {
                $query->where('tracking_number', $tracking_number);
            }
            $query = $query->where(function ($query) use ($user_id) {
                $query->where('user_id', $user_id)
                        ->orWhere('received_by', $user_id)
                        ->orWhere('transit_by', $user_id)
                        ->orWhere('destination_person', $user_id);
            });
            // Load additional data for display


            $parcels = $query->paginate(200);

            foreach ($parcels as $key => $val) {
                $val->user_name = User::find($val->user_id)->full_name;

                $receive_by = User::find($val->received_by);
                $val->receiver_name = $receive_by->full_name;
                $val->current_company_name = Company::find($receive_by->company)->title;
                $val->destination_company_name = Company::find($val->destination)->title;
                $val->destination_person_name = User::find($val->destination_person)->full_name;
                $val->from_company_name = Company::find($val->from_company)->title;
                $val->image_source = url('') . '/parcels/' . $val->photo;
            }
            $return['parcels'] = $parcels;
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

            $user = User::find($request->user->id);

            $validator = Validator::make($request->all(), [
                        'title' => 'required',
                        'item_type' => 'required',
                        'description' => 'nullable',
                        'destination' => 'required',
                        'destination_person' => 'required',
                        'challan_no' => 'nullable',
                        'reference' => 'nullable',
                        'photo' => 'nullable',
                        'qty' => 'nullable',
                        'buyer' => 'nullable',
                        'method' => 'required',
            ]);

            if ($validator->fails()) {
                $return['errors'] = $validator->errors();
                return $this->response($return, $statusCode);
            }

            $parcel = new Parcel;
            $parcel->title = $request->input('title');
            $parcel->destination = $request->input('destination');
            $parcel->destination_person = $request->input('destination_person');
            $parcel->description = $request->input('description');
            $parcel->user_id = $user->id;
            $parcel->received_by = $user->id;
            $parcel->received_date = now();
            $parcel->from_company = $user->company;
            $parcel->status = 'Issued';
            $parcel->challan_no = $request->input('challan_no');
            $parcel->reference = $request->input('reference');
            $parcel->transit_by = $user->id;
            $parcel->item_type = $request->input('item_type');
            $parcel->qty = $request->input('qty');
            $parcel->buyer = $request->input('buyer');
            $parcel->method = $request->input('method');

            if (isset($_FILES['photo']['name'])) {
                $public_path = public_path();
                $path = $public_path . '/' . "parcels";
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
                    $parcel->photo = $file_name;
                }
            }

            if ($parcel->save()) {
                $notification = new \App\Models\Notification;
                $notification->title = $user->full_name . " has booked a parcel for you";
                $notification->receiver = $parcel->destination_person;
                $notification->url = "/parcel-details/" . $parcel->id;
                $notification->description = "Please Take Necessary Action";
                $notification->is_read = 0;
                $notification->save();

                $return['data'] = $parcel;
                $return['status'] = 'success';
                $statusCode = 200;
            } else {
                $return['status'] = 'error';
                $return['message'] = 'Failed to save parcel';
            }

            return $this->response($return, $statusCode);
        } catch (\Throwable $ex) {
            return $this->error(['status' => 'error', 'main_error_message' => $ex->getMessage()]);
        }
    }

    public function show(Request $request) {
        try {

            $statusCode = 422;
            $return = [];
            $tracking_number = $request->input('tracking_number');
            $parcel = Parcel::where('tracking_number', $tracking_number)->first();

            if ($parcel) {
                $user = User::where('id', $parcel->user_id)->first();
                $parcel->user_name = $user->full_name;
                $receiver = User::where('id', $parcel->received_by)->first();
                $parcel->receiver_name = $receiver->full_name;
                $company = Company::where('id', $parcel->destination)->first();
                $parcel->to = $company->title;
                $destination_person = User::where('id', $parcel->destination_person)->first();
                $parcel->destination_person_name = $destination_person->full_name;
                $from_company = Company::where('id', $parcel->from_company)->first();
                $parcel->from_company_name = $from_company->title;
                $scan_url = "/parcel-details/" . $parcel->tracking_number;
                $parcel->scan_url = $scan_url;
                $path = url('') . '/parts/' . $parcel->photo;
                $parcel->img_url = $path;

                $buyer = \App\Models\Buyer::find($parcel->buyer);

                if ($buyer) {
                    $parcel->buyer_name = $buyer->name;
                } else {
                    $parcel->buyer_name = "N/A";
                }


                $return['data'] = $parcel;
                $return['status'] = 'success';
                $statusCode = 200;
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
            $id = $request->input('id');
            $user = User::find($request->user->id);

            $validator = Validator::make($request->all(), [
                        'destination' => 'required',
                        'destination_person' => 'required',
                        'item_type' => 'required',
                        'title' => 'required',
                        'qty' => 'nullable',
                        'buyer' => 'nullable',
                        'method' => 'required',
                        'description' => 'nullable',
                        'challan_no' => 'nullable',
                        'reference' => 'nullable',
                        'photo' => 'nullable',
            ]);

            if ($validator->fails()) {
                $return['errors'] = $validator->errors();
                return $this->response($return, $statusCode);
            }

            $parcel = Parcel::find($id);
            if (!$parcel) {
                $return['status'] = 'error';
                $return['message'] = 'Parcel not found';
                return $this->response($return, $statusCode);
            }

            $parcel->title = $request->input('title');
            $parcel->destination = $request->input('destination');
            $parcel->destination_person = $request->input('destination_person');
            $parcel->description = $request->input('description');
            $parcel->challan_no = $request->input('challan_no');
            $parcel->reference = $request->input('reference');
            $parcel->item_type = $request->input('item_type');
            $parcel->qty = $request->input('qty');
            $parcel->buyer = $request->input('buyer');
            $parcel->method = $request->input('method');

            if (isset($_FILES['photo']['name'])) {
                $public_path = public_path();
                $path = $public_path . '/' . "parcels";
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
                    $parcel->photo = $file_name;
                }
            }

            if ($parcel->save()) {
                $notification = new \App\Models\Notification;
                $notification->title = $user->full_name . " has updated the parcel details";
                $notification->receiver = $parcel->destination_person;
                $notification->url = "/parcel-details/" . $parcel->tracking_number;
                $notification->description = "Please review the updated details";
                $notification->is_read = 0;
                $notification->save();
                $return['data'] = $parcel;
                $return['status'] = 'success';
                $statusCode = 200;
            } else {
                $return['status'] = 'error';
                $return['message'] = 'Failed to update parcel';
            }
            return $this->response($return, $statusCode);
        } catch (\Throwable $ex) {
            return $this->error(['status' => 'error', 'main_error_message' => $ex->getMessage()]);
        }
    }

    public function receive(Request $request) {
        try {
            $statusCode = 422;
            $return = [];

            $id = $request->input('id');
            $user = User::find($request->user->id);

            $user_id = $request->user->id;
            $parcel = Parcel::where('id', $id)->first();
            $parcel->received_by = $user_id;
            $parcel->received_date = now();

            if ($user_id == $parcel->destination_person) {
                $parcel->status = 'Completed';
            } else {
                $parcel->status = 'In Transit';
                $parcel->transit_by = $user_id;
            }
            if ($parcel->save()) {
                $notification = new \App\Models\Notification;
                $notification->title = $user->full_name . " has received the parcel that you booked";
                $notification->receiver = $parcel->user_id;
                $notification->url = "/parcel-details/" . $parcel->tracking_number;
                $notification->description = "Please review the updated details";
                $notification->is_read = 0;
                $notification->save();

                $return['data'] = $parcel;
                $return['status'] = 'success';
                $statusCode = 200;
            }

            return $this->response($return, $statusCode);
        } catch (\Throwable $ex) {
            return $this->error(['status' => 'error', 'main_error_message' => $ex->getMessage()]);
        }
    }

    public function destroy(Request $request) {
        try {
            $statusCode = 422;
            $return = [];
            $id = $request->input('id');

            $user = User::find($request->user->id);

            $parcel = Parcel::find($id);
            if (!$parcel) {
                $return['status'] = 'error';
                $return['message'] = 'Parcel not found';
                return $this->response($return, $statusCode);
            }

            // Check if the user is the owner and the status is "Issued"
            if ($parcel->user_id != $user->id || $parcel->status != 'Issued') {
                $return['status'] = 'error';
                $return['message'] = 'Unauthorized to delete this parcel';
                return $this->response($return, $statusCode);
            }

            // Delete the photo if it exists
            if ($parcel->photo) {
                $public_path = public_path();
                $photo_path = $public_path . '/' . "parcels" . '/' . $parcel->photo;
                if (file_exists($photo_path)) {
                    unlink($photo_path);
                }
            }

            if ($parcel->delete()) {
                $notification = new \App\Models\Notification;
                $notification->title = $user->full_name . " has deleted the parcel";
                $notification->receiver = $parcel->destination_person;
                $notification->description = "The parcel has been removed.";
                $notification->is_read = 0;
                $notification->save();

                $return['status'] = 'success';
                $statusCode = 200;
            } else {
                $return['status'] = 'error';
                $return['message'] = 'Failed to delete parcel';
            }

            return $this->response($return, $statusCode);
        } catch (\Throwable $ex) {
            return $this->error(['status' => 'error', 'main_error_message' => $ex->getMessage()]);
        }
    }

}
