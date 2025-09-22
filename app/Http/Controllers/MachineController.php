<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Machine;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\DB;
use Maatwebsite\Excel\Facades\Excel;

class MachineController extends Controller {

    public function index(Request $request) {
        try {
            $statusCode = 422;
            $return = [];
            $query = Machine::query()->orderBy('created_at', 'desc');

            // Filter by company_id
            $company_id = $request->input('company_id');
            if ($company_id) {
                $query->where('company_id', $company_id);
            }

            // Filter by efficiency
            if ($request->has('efficiency')) {
                if ($request->input('efficiency') == 'A') {
                    $query->where('efficiency', '>=', 75);
                } elseif ($request->input('efficiency') == 'B') {
                    $query->whereBetween('efficiency', [56, 74]);
                } elseif ($request->input('efficiency') == 'C') {
                    $query->whereBetween('efficiency', [41, 55]);
                } elseif ($request->input('efficiency') == 'D') {
                    $query->where('efficiency', '<=', 40);
                } elseif ($request->input('efficiency') == 'I') {
                    // Add company_id condition only if it's present in the request
                    if ($request->has('company_id')) {
                        $query->where(function ($query) use ($request) {
                            $query->where('status', 'Idle')->orWhere('unit', 'IDLE');
                        });
                    } else {
                        // If company_id is not present, still filter by efficiency 'I'
                        $query->where('status', 'Idle')->orWhere('unit', 'IDLE');
                    }
                }
            }

            // Search functionality
            $searchTerm = $request->input('search');
            if ($searchTerm) {
                $query->where(function ($query) use ($searchTerm) {
                    $query->where('title', 'LIKE', "%$searchTerm%")
                            ->orWhere('brand', 'LIKE', "%$searchTerm%")
                            ->orWhere('model', 'LIKE', "%$searchTerm%")
                            ->orWhere('serial_number', '=', $searchTerm)
                            ->orWhere('status', 'LIKE', "%$searchTerm%")
                            ->orWhere('title', 'LIKE', "%$searchTerm%");
                });
            }
            $machines = $query->paginate(100);
            if ($machines->isNotEmpty()) {
                foreach ($machines as $val) {
                    $company = \App\Models\Company::where('id', $val->company_id)->first();
                    $val->company = $company->title;
                    $val->image_source = url('') . '/machines/' . $val->photo;
                }
                $return['machines'] = $machines;
                $statusCode = 200;
            } else {
                $return['status'] = 'error';
            }

            return response()->json($return, $statusCode);
        } catch (\Throwable $ex) {
            return response()->json(['status' => 'error', 'main_error_message' => $ex->getMessage()], 500);
        }
    }

    public function store(Request $request) {

        try {
            $statusCode = 422;
            $return = [];
            $user_id = $request->user->id;
            $validator = Validator::make($request->all(), [
                        'title' => 'required',
                        'model' => 'required',
                        'company_id' => 'required',
                        'type' => 'required',
                        'category' => 'required',
                        'ownership' => 'required',
                        'purchase_date' => 'required',
                        'purchase_value' => 'required',
            ]);

            if ($validator->fails()) {
                return response()->json([
                            'errors' => $validator->errors()
                                ], 422);
            }

            $last_machine = Machine::orderBy('id', 'desc')->first();
            $machine = new Machine;
            if ($last_machine) {
                $machine->serial_number = "MCN-" . ($last_machine->id + 1);
            } else {
                $machine->serial_number = "MCN-1";
            }

            $machine->user_id = $user_id;
            $machine->title = $request->input('title');
            $machine->brand = $request->input('brand');
            $machine->model = $request->input('model');
            $machine->type = $request->input('type');
            $machine->unit = $request->input('unit');
            $machine->reference = $request->input('reference');
            $machine->efficiency = $request->input('efficiency');
            $machine->note = $request->input('note');
            $machine->description = $request->input('description');
            $machine->company_id = $request->input('company_id');
            $machine->status = $request->input('status');
            $machine->purchase_date = $request->input('purchase_date');
            $machine->purchase_value = $request->input('purchase_value');
            $machine->purchase_value_bdt = $request->input('purchase_value_bdt');
            $machine->warranty_ends_at = $request->input('warranty_ends_at');
            $machine->guarantee_ends_at = $request->input('guarantee_ends_at');
            $machine->ownership = $request->input('ownership');
            $machine->category = $request->input('category');
            $machine->status = ($machine->efficiency < 40) ? "Inactive" : "Active";

            if (isset($_FILES['photo']['name'])) {
                $public_path = public_path();
                $path = $public_path . '/' . "machines";
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
                    $machine->photo = $file_name;
                }
            }

            $machine->save();

            $statusCode = 200;
            $return['data'] = $machine;
            $return['status'] = 'success';

            return $this->response($return, $statusCode);
        } catch (\Throwable $ex) {
            return $this->error(['status' => "error", 'main_error_message' => $ex->getMessage()]);
        }
    }

    public function store_bulk(Request $request) {

        try {
            $statusCode = 422;
            $return = [];
            $user_id = $request->user->id;

            if ($request->hasFile('file')) {
                $path = $request->file('file')->storeAs('exels', uniqid() . '.' . $request->file('file')->getClientOriginalExtension(), 'local');
                $fullPath = storage_path('app/' . $path);
                if (file_exists($fullPath)) {
                    if ($request->file('file')->getClientOriginalExtension() == 'xlsx') {
                        $machines = Excel::toArray([], $fullPath)[0];
                        array_shift($machines);
                        // Insert employee data into database
                        foreach ($machines as $key => $data) {
                            $machine = new Machine();
                            $machine->serial_number = "MCN-" . ($key + 1);
                            $machine->title = $data[0] ?? "N/A";
                            $machine->brand = $data[1] ?? "N/A";
                            $machine->model = $data[2] ?? "N/A";
                            $machine->type = $data[3] ?? "N/A";
                            $machine->unit = $data[4] ?? "N/A";
                            $machine->reference = $data[5] ?? "N/A";
                            $machine->efficiency = $data[6] ?? 0;
                            $machine->status = ($machine->efficiency < 40) ? "Inactive" : "Active";
                            $machine->description = $data[7] ?? "N/A";
                            $machine->company_id = $data[8] ?? 1;
                            $machine->purchase_value = 0;
                            $machine->purchase_value_bdt = 0;
                            $machine->purchase_date = date('Y-m-d');
                            $machine->user_id = $user_id;
                            $machine->save();
                        }
                        $statusCode = 200;
                        $return['status'] = 'success';
                        $return['data'] = $machines;
                    }
                } else {
                    $return['errors']['file'] = 'Error uploading file';
                }
            } else {
                $return['errors']['file'] = 'Please upload a file';
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
            $machine = Machine::find($id);

            if ($machine) {
                $user = \App\Models\User::where('id', $machine->user_id)->first();
                $machine->user = $user->full_name;
                $company = \App\Models\Company::where('id', $machine->company_id)->first();
                $machine->company = $company->title;

                if ($machine->photo) {
                    $machine->image_source = url('') . '/machines/' . $machine->photo;
                } else {
                    $machine->image_source = '';
                }




                $return['data'] = $machine;
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

    public function toggleStaus(Request $request) {
        try {
            $statusCode = 422;
            $return = [];
            $id = $request->input('id');
            $status = $request->input('status');
            $machine = Machine::find($id);

            if ($machine) {
                $machine->status = $status;
                $machine->save();
                $return['data'] = $machine;
                $statusCode = 200;
                $return['status'] = 'success';
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
            $user_id = $request->user->id;
            $id = $request->input('id');

            $validator = Validator::make($request->all(), [
                        'title' => 'required',
                        'model' => 'required',
                        'company_id' => 'required',
                        'type' => 'required',
                        'category' => 'required',
                        'ownership' => 'required',
                        'purchase_date' => 'required',
                        'purchase_value' => 'required',
            ]);

            if ($validator->fails()) {
                return response()->json([
                            'errors' => $validator->errors()
                                ], 422);
            }

            $machine = Machine::findOrFail($id);
            $machine->title = $request->input('title');
            $machine->brand = $request->input('brand');
            $machine->model = $request->input('model');
            $machine->type = $request->input('type');
            $machine->unit = $request->input('unit');
            $machine->reference = $request->input('reference');
            $machine->efficiency = $request->input('efficiency');
            $machine->note = $request->input('note');
            $machine->description = $request->input('description');
            $machine->company_id = $request->input('company_id');
            $machine->status = $request->input('status');
            $machine->purchase_date = $request->input('purchase_date');
            $machine->purchase_value = $request->input('purchase_value');
            $machine->purchase_value_bdt = $request->input('purchase_value_bdt');
            $machine->warranty_ends_at = $request->input('warranty_ends_at');
            $machine->guarantee_ends_at = $request->input('guarantee_ends_at');
            $machine->ownership = $request->input('ownership');
            $machine->category = $request->input('category');
            $machine->status = ($machine->efficiency < 40) ? "Inactive" : "Active";

            if ($request->hasFile('photo')) {
                $public_path = public_path();
                $path = $public_path . '/' . "machines";
                $pathinfo = pathinfo($request->file('photo')->getClientOriginalName());
                $basename = strtolower(str_replace(' ', '_', $pathinfo['filename']));
                $extension = strtolower($pathinfo['extension']);
                $file_name = $basename . '.' . $extension;
                $finalpath = $path . '/' . $file_name;
                if (file_exists($finalpath)) {
                    $file_name = $basename . time() . '.' . $extension;
                    $finalpath = $path . '/' . $file_name;
                }
                if ($request->file('photo')->move($path, $file_name)) {
                    $machine->photo = $file_name;
                }
            }

            $machine->save();
            $statusCode = 200;
            $return['data'] = $machine;
            $return['status'] = 'success';

            return $this->response($return, $statusCode);
        } catch (\Throwable $ex) {
            return $this->error(['status' => "error", 'main_error_message' => $ex->getMessage()]);
        }
    }

    public function destroy(Request $request) {
        try {
            $statusCode = 422;
            $return = [];
            $user_id = $request->user->id;
            $id = $request->input('id');

            $machine = Machine::findOrFail($id);

            // Check if the user has permission to delete this machine
            if ($machine->user_id !== $user_id) {
                return response()->json([
                            'error' => 'You do not have permission to delete this machine.'
                                ], 403);
            }

            // Delete the associated image file
            if ($machine->photo) {
                $public_path = public_path();
                $image_path = $public_path . '/machines/' . $machine->photo;
                if (file_exists($image_path)) {
                    unlink($image_path);
                }
            }

            // Delete the machine instance
            $machine->delete();
            $statusCode = 200;
            $return['status'] = 'success';
            $return['message'] = 'Machine deleted successfully';

            return $this->response($return, $statusCode);
        } catch (\Throwable $ex) {
            return $this->error(['status' => "error", 'main_error_message' => $ex->getMessage()]);
        }
    }

}
