<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Holiday;
use Illuminate\Support\Facades\Validator;
use Carbon\Carbon;
use App\Models\Payroll;
use App\Models\User;

class PayrollController extends Controller {

    public function index(Request $request) {
        try {
            $company_id = $request->input('company_id');
            $department = $request->input('department');
            $designation = $request->input('designation');

            // Query with relationships to prevent N+1 issue
            $query = Payroll::query()
                    ->leftJoin('companies', 'payrolls.company_id', '=', 'companies.id')
                    ->leftJoin('departments', 'payrolls.department_id', '=', 'departments.id')
                    ->leftJoin('designations', 'payrolls.designation_id', '=', 'designations.id')
                    ->select('payrolls.*', 'companies.title as company_name', 'departments.title as department_title', 'designations.title as designation_title');

            if (!empty($company_id)) {
                $query->where('payrolls.company_id', $company_id);
            }
            if (!empty($department)) {
                $query->where('payrolls.department_id', $department);
            }
            if (!empty($designation)) {
                $query->where('payrolls.designation_id', $designation);
            }

            $users = $query->get();

            return response()->json([
                        'status' => 'success',
                        'data' => $users
                            ], 200);
        } catch (\Throwable $ex) {
            return response()->json([
                        'status' => 'error',
                        'message' => 'An error occurred while fetching users.',
                        'error_details' => $ex->getMessage()
                            ], 500);
        }
    }

    public function index_origin(Request $request) {
        try {
            $company_id = $request->input('company_id');
            $department = $request->input('department');
            $designation = $request->input('designation');

            $query = Payroll::query();

            if (!empty($company_id)) {
                $query->where('company_id', $company_id); // Ensure correct column name
            }
            if (!empty($department)) {
                $query->where('department_id', $department);
            }

            if (!empty($designation)) {
                $query->where('designation_id', $designation);
            }


            $users = $query->get();

            foreach ($users as $user) {
                $company = \App\Models\Company::find($user->company_id);
                $user->company_name = $company->title ?? "N/A";

                $department = \App\Models\Department::find($user->department_id);
                $user->department_title = $department->title ?? "N/A";

                $designation = \App\Models\Designation::find($user->designation_id);
                $user->designation_title = $designation->title ?? "N/A";
            }



            return response()->json([
                        'status' => 'success',
                        'data' => $users
                            ], 200);
        } catch (\Throwable $ex) {
            return response()->json([
                        'status' => 'error',
                        'message' => 'An error occurred while fetching users.', // Fixed incorrect error message
                        'error_details' => $ex->getMessage()
                            ], 500);
        }
    }

    public function store(Request $request) {
        try {
            $statusCode = 422;
            $return = [];
            $validator = Validator::make($request->all(), [
                'full_name' => 'required',
                'staff_id' => 'required|unique:payrolls,staff_id',
                'company_id' => 'required',
                'department_id' => 'required',
                'designation_id' => 'required',
                'basic_salary' => 'required',
                'house_rent' => 'required',
                'medical_allowance' => 'required',
                'transport_allowance' => 'required',
                'food_allowance' => 'required',
                'gross_salary' => 'required',
            ]);

            if ($validator->fails()) {
                $return['errors'] = $validator->errors();
                $statusCode = 422;
            } else {
                $payroll = new Payroll;
                $payroll->full_name = $request->input('full_name');
                $payroll->staff_id = $request->input('staff_id');

                $payroll->company_id = $request->input('company_id');
                $payroll->department_id = $request->input('department_id');
                $payroll->designation_id = $request->input('designation_id');
                $payroll->basic_salary = $request->input('basic_salary');
                $payroll->house_rent = $request->input('house_rent');
                $payroll->medical_allowance = $request->input('medical_allowance');
                $payroll->transport_allowance = $request->input('transport_allowance');
                $payroll->food_allowance = $request->input('food_allowance');
                $payroll->gross_salary = $request->input('gross_salary');

                $payroll->save();
                $return['data'] = $payroll;
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
            $id = $request->input('id');

            // Find the payroll record
            $payroll = Payroll::find($id);

            if (!$payroll) {
                return $this->error(['status' => 'error', 'main_error_message' => 'Payroll record not found.'], 404);
            }

            // Validate request data
            $validator = Validator::make($request->all(), [
                'full_name' => 'required',
                'staff_id' => 'required|unique:payrolls,staff_id,' . $id,
                'company_id' => 'required',
                'department_id' => 'required',
                'designation_id' => 'required',
                'basic_salary' => 'required',
                'house_rent' => 'required',
                'medical_allowance' => 'required',
                'transport_allowance' => 'required',
                'food_allowance' => 'required',
                'gross_salary' => 'required',
            ]);

            if ($validator->fails()) {
                $return['errors'] = $validator->errors();
            } else {
                // Update payroll details
                $payroll->full_name = $request->input('full_name');
                $payroll->staff_id = $request->input('staff_id');
                $payroll->company_id = $request->input('company_id');
                $payroll->department_id = $request->input('department_id');
                $payroll->designation_id = $request->input('designation_id');
                $payroll->basic_salary = $request->input('basic_salary');
                $payroll->house_rent = $request->input('house_rent');
                $payroll->medical_allowance = $request->input('medical_allowance');
                $payroll->transport_allowance = $request->input('transport_allowance');
                $payroll->food_allowance = $request->input('food_allowance');
                $payroll->gross_salary = $request->input('gross_salary');

                $payroll->save();

                $return['data'] = $payroll;
                $statusCode = 200;
                $return['status'] = 'success';
            }

            return $this->response($return, $statusCode);
        } catch (\Throwable $ex) {
            return $this->error(['status' => 'error', 'main_error_message' => $ex->getMessage()]);
        }
    }
}
