<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Holiday;
use Illuminate\Support\Facades\Validator;
use Carbon\Carbon;

class HolidayController extends Controller {

    public function index(Request $request) {
        try {
            $year = $request->input('year');

            $holidays = Holiday::whereYear('date', $year)
                    ->orderBy('date', 'asc') // Sorting by date in ascending order
                    ->get();

            return response()->json([
                        'status' => 'success',
                        'data' => $holidays ?? []
                            ], 200);
        } catch (\Throwable $ex) {
            return response()->json([
                        'status' => 'error',
                        'message' => 'An error occurred while fetching holidays.',
                        'main_error_message' => $ex->getMessage()
                            ], 500);
        }
    }

    public function store(Request $request) {
        try {
            $statusCode = 422;
            $return = [];
//           input_variable
            $user_id = $request->user->id;

            $validator = Validator::make($request->all(), [
                'title' => 'required',
                'date' => 'required|unique:holidays,date',
            ]);

            if ($validator->fails()) {
                $return['errors'] = $validator->errors();
                $statusCode = 422;
            } else {
                $holiday = new Holiday;
                $holiday->title = $request->input('title');
                $holiday->date = $request->input('date');
                $holiday->user_id = $user_id;
                $holiday->save();
                $return['data'] = $holiday;
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

            // Validate input
            $validator = Validator::make($request->all(), [
                'id' => 'required|exists:holidays,id',
                'title' => 'required',
                'date' => 'required|unique:holidays,date,' . $request->input('id'),
            ]);

            if ($validator->fails()) {
                $return['errors'] = $validator->errors();
                $statusCode = 422;
            } else {
                // Find holiday by ID
                $holiday = Holiday::find($request->input('id'));

                if ($holiday) {
                    $holiday->title = $request->input('title');
                    $holiday->date = $request->input('date');
                    $holiday->save();

                    $return['data'] = $holiday;
                    $statusCode = 200;
                    $return['status'] = 'success';
                } else {
                    $return['status'] = 'error';
                    $return['message'] = 'Holiday not found';
                }
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

            $holiday = Holiday::find($request->input('id'));

            if ($holiday) {
                $holiday->delete();
                $return['status'] = 'success';
                $return['message'] = 'Holiday deleted successfully';
                $statusCode = 200;
            } else {
                $return['status'] = 'error';
                $return['message'] = 'Holiday not found';
            }


            return $this->response($return, $statusCode);
        } catch (\Throwable $ex) {
            return $this->error(['status' => "error", 'main_error_message' => $ex->getMessage()]);
        }
    }
}
