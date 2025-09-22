<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Hscode;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\DB;
use Maatwebsite\Excel\Facades\Excel;

class HscodeController extends Controller {

    public function index(Request $request) {
        try {
            $statusCode = 422;
            $return = [];
            $searchTerm = $request->input('search');

            if ($searchTerm) {
                $hscodes = Hscode::where(function ($query) use ($searchTerm) {
                            $query->where('code_8', 'LIKE', "%$searchTerm%")
                                    ->orWhere('code_10', 'LIKE', "%$searchTerm%")
                                    ->orWhere('description', 'LIKE', "%$searchTerm%");
                        })->paginate(100);
            } else {
                $hscodes = Hscode::paginate(100);
                $hscodes_all = Hscode::all();
            }
            if ($hscodes->isNotEmpty()) {
                $return['hscodes'] = $hscodes;
                $return['hscodes_all'] = $hscodes_all;
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

            $existance = Hscode::get();
            if ($existance->isNotEmpty()) {
                $return['errors']['file'] = 'Please Delete Previous Items First';
            } else {
                if ($request->hasFile('file')) {
                    $path = $request->file('file')->storeAs('exels', uniqid() . '.' . $request->file('file')->getClientOriginalExtension(), 'local');
                    $fullPath = storage_path('app/' . $path);

                    if (file_exists($fullPath)) {
                        if ($request->file('file')->getClientOriginalExtension() == 'xlsx') {
                            $hscodes = Excel::toArray([], $fullPath)[0];
                            array_shift($hscodes);
//                     Insert employee data into database
                            foreach ($hscodes as $data) {
                                $hscode = new Hscode();
                                $hscode->hs = $data[0] ?? 0;
                                $hscode->code_8 = $data[1] ?? 0;
                                $hscode->code_10 = $data[2] ?? 0;
                                $hscode->unit = $data[3] ?? "N/A";
                                $hscode->description = $data[4] ?? "N/A";
                                $hscode->cd = $data[5] ?? 0;
                                $hscode->sd = $data[6] ?? 0;
                                $hscode->vat = $data[7] ?? 0;
                                $hscode->ait = $data[8] ?? 0;
                                $hscode->at = $data[9] ?? 0;
                                $hscode->rd = $data[10] ?? 0;
                                $hscode->save();
                            }
                            $statusCode = 200;
                            $return['status'] = 'success';
                            $return['data'] = $hscodes;
                        }
                    } else {
                        $return['errors']['file'] = 'Error uploading file';
                    }
                } else {
                    $return['errors']['file'] = 'Please upload a file';
                }
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
            $hscode = Hscode::find($id);

            if ($hscode) {
                $return['data'] = $hscode;
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
            $hscode = Hscode::find($id);

            if ($hscode) {
                $hscode->status = $status;
                $hscode->save();
                $return['data'] = $hscode;
                $statusCode = 200;
                $return['status'] = 'success';
            }


            return $this->response($return, $statusCode);
        } catch (\Throwable $ex) {
            return $this->error(['status' => "error", 'main_error_message' => $ex->getMessage()]);
        }
    }

    public function toggleStatusBulk(Request $request) {
        try {
            $statusCode = 422;
            $return = [];
            $csv = $request->input('csv');

            $csvArray = explode(',', $csv);

            foreach ($csvArray as $val) {
                // Find the Hscode with the given code_10 value
                $hscode = Hscode::where('code_10', $val)->first();

                // Check if $hscode exists before attempting to update it
                if ($hscode) {
                    $hscode->status = 'active';
                    $hscode->save();
                } else {
                    // Handle case where Hscode with code_10 value $val was not found
                    \Log::error("Hscode with code_10 value $val not found.");
                }
            }

            $return['data'] = $csvArray;
            $statusCode = 200;
            $return['status'] = 'success';

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
            $hscode = Hscode::find($id);

            $validator = Validator::make($request->all(), [
                        'code' => 'required',
                        'description' => 'required',
            ]);
            if ($validator->fails()) {
                $return['errors'] = $validator->errors();
                $statusCode = 422;
            } else {
                $hscode->code = $request->input('code');
                $hscode->description = $request->input('description');
                $hscode->save();
                $return['data'] = $hscode;
                $statusCode = 200;
                $return['status'] = 'success';
            }
            return $this->response($return, $statusCode);
        } catch (\Throwable $ex) {
            return $this->error(['status' => "error", 'main_error_message' => $ex->getMessage()]);
        }
    }

    public function destroy(Request $request) {
        try {
            $statusCode = 422;
            $return = [];

            // Truncate the table
            DB::table('hscodes')->truncate();

            $return['status'] = 'success';
            $statusCode = 200;

            return $this->response($return, $statusCode);
        } catch (\Throwable $ex) {
            return $this->error(['status' => "error", 'main_error_message' => $ex->getMessage()]);
        }
    }

}
