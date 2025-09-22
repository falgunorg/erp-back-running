<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\User;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Storage;
use Maatwebsite\Excel\Facades\Excel;
use Illuminate\Support\Facades\DB;
use App\Models\ContactData;
use App\Models\EmployementData;
use App\Models\LeaveData;
use App\Models\FinanceData;
use Carbon\Carbon;
use App\Models\Style;
use App\Models\Supplier;
use App\Models\Hscode;
use App\Models\Part;

class SettingController extends Controller {

    public function store_bulk_parts(Request $request) {

        try {
            $statusCode = 422;
            $return = [];

            if ($request->hasFile('file')) {
                $path = $request->file('file')->storeAs('exels', uniqid() . '.' . $request->file('file')->getClientOriginalExtension(), 'local');
                $fullPath = storage_path('app/' . $path);
                if (file_exists($fullPath)) {
                    if ($request->file('file')->getClientOriginalExtension() == 'xlsx') {
                        $parts = Excel::toArray([], $fullPath)[0];
                        array_shift($parts);
//                     Insert employee data into database
                        foreach ($parts as $data) {
                            $part = new Part();
                            $part->title = $data[0] ?? "N/A";
                            $part->unit = $data[1] ?? "N/A";
                            $part->type = $data[3] ?? "N/A";
                            $part->model = "N/A";
                            $part->user_id = 36;
                            $part->company_id = 1;

                            if ($part->save()) {
                                $substore = new \App\Models\SubStore;
                                $substore->part_id = $part->id;
                                $substore->company_id = $part->company_id;
                                $substore->qty = $data[2] ?? 0; //QTY FROM FRONTEND
                                $substore->save();
                            }
                        }
                        $statusCode = 200;
                        $return['status'] = 'success';
                        $return['data'] = $parts;
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

    public function store_bulk_hscode(Request $request) {

        try {
            $statusCode = 422;
            $return = [];

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
                            $hscode->code = $data[0] ?? "N/A";
                            $hscode->description = $data[1] ?? "N/A";
                            $hscode->cd = $data[2] ?? 0;
                            $hscode->sd = $data[3] ?? 0;
                            $hscode->vat = $data[4] ?? 0;
                            $hscode->ait = $data[5] ?? 0;
                            $hscode->at = $data[6] ?? 0;
                            $hscode->rd = $data[7] ?? 0;
                            $hscode->exd = $data[8] ?? 0;
                            $hscode->tti = $data[9] ?? 0;
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

            return $this->response($return, $statusCode);
        } catch (\Throwable $ex) {
            return $this->error(['status' => "error", 'main_error_message' => $ex->getMessage()]);
        }
    }

    public function store(Request $request) {
        try {
            $statusCode = 422;
            $return = [];
            $user_id = $request->user->id;
            if ($request->hasFile('file')) {
                $path = $request->file('file')->storeAs('exels', uniqid() . '.' . $request->file('file')->getClientOriginalExtension(), 'local');
                $fullPath = storage_path('app/' . $path);

                if (file_exists($fullPath)) {
                    if ($request->file('file')->getClientOriginalExtension() == 'xlsx') {
                        $suppliers = Excel::toArray([], $fullPath)[0];
                        array_shift($suppliers);
                        foreach ($suppliers as $data) {
                            $supplier = new Supplier();
                            $supplier->company_name = $data[0] ?? "N/A";
                            $supplier->email = $data[1] ?? "N/A";
                            $supplier->attention_person = $data[2] ?? "N/A";
                            $supplier->office_number = $data[3] ?? "N/A";
                            $supplier->mobile_number = $data[4] ?? "N/A";
                            $supplier->address = $data[5] ?? "N/A";
                            $supplier->state = $data[6] ?? "N/A";
                            $supplier->postal_code = $data[7] ?? "N/A";
                            $supplier->country = $data[8] ?? "N/A";
                            $supplier->product_supply = $data[9] ?? "N/A";
                            $supplier->vat_reg_number = $data[10] ?? "N/A";
                            $supplier->type = $data[10] ?? "N/A";
                            $supplier->added_by = $user_id;
                            $supplier->save();
                        }
                        $statusCode = 200;
                        $return['status'] = 'success';
                        $return['data'] = $suppliers;
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

    public function store_bulk_style(Request $request) {

        try {
            $statusCode = 422;
            $return = [];

            $user_id = $request->user->id;

            if ($request->hasFile('file')) {
                $path = $request->file('file')->storeAs('exels', uniqid() . '.' . $request->file('file')->getClientOriginalExtension(), 'local');
                $fullPath = storage_path('app/' . $path);

                if (file_exists($fullPath)) {
                    if ($request->file('file')->getClientOriginalExtension() == 'xlsx') {
                        $styles = Excel::toArray([], $fullPath)[0];
                        array_shift($styles);
//                     Insert employee data into database
                        foreach ($styles as $data) {
                            $style = new Style();
                            $style->buyer_id = $data[0] ?? "N/A";
                            $style->title = $data[1] ?? "N/A";
                            $style->user_id = $user_id;
                            $style->save();
                        }
                        $statusCode = 200;
                        $return['status'] = 'success';
                        $return['data'] = $styles;
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

    public function store_old(Request $request) {
        try {
            $statusCode = 422;
            $return = [];

            if ($request->hasFile('file')) {
                $path = $request->file('file')->storeAs('exels', uniqid() . '.' . $request->file('file')->getClientOriginalExtension(), 'local');
                $fullPath = storage_path('app/' . $path);

                if (file_exists($fullPath)) {
                    $users = [];

                    if ($request->file('file')->getClientOriginalExtension() == 'csv') {
                        if (($open = fopen($fullPath, 'r')) !== false) {
                            $skipFirstRow = false; // add flag variable
                            while (($data = fgetcsv($open, 1000, ',')) !== false) {
                                if (!$skipFirstRow) { // skip first row
                                    $skipFirstRow = true;
                                    continue;
                                }
                                $user = new User();
                                $user->full_name = $data[0] ?? "";
                                $user->short_name = $data[1] ?? "";
                                $user->login_id = $data[2] ?? "";
                                $user->password = \App\Libraries\Tokenizer::password($data[3] ?? "");
                                $user->nationality = $data[4] ?? "";
                                $user->employee_id = $data[5] ?? "";
                                $user->gender = $data[6] ?? "";
                                $dateOfBirth = intval($data[7]);
                                $user->date_of_birth = \PhpOffice\PhpSpreadsheet\Shared\Date::excelToDateTimeObject($dateOfBirth)->format('Y-m-d');
                                $user->race = $data[8] ?? "";
                                $user->religion = $data[9] ?? "";

                                $user->marital_status = $data[10] ?? "";
                                $user->ic_num = $data[11] ?? "";
                                $user->ic_copy = $data[12] ?? "";

                                $ppt_expired = intval($data[13]);
                                $user->ppt_expired = \PhpOffice\PhpSpreadsheet\Shared\Date::excelToDateTimeObject($ppt_expired)->format('Y-m-d');

                                $user->ppt_copy = $data[14] ?? "";
                                $user->photo = $data[15] ?? "";

//                                add other data
                                if ($user->save()) {
                                    $contact_data = new ContactData([
                                        'phone_code' => $data[16] ?? "",
                                        'mobile_number' => $data[17] ?? "",
                                        'user_id' => $user->id,
                                        'house_number' => $data[18] ?? "",
                                        'email' => $data[19] ?? "",
                                        'address' => $data[20] ?? "",
                                        'city' => $data[21] ?? "",
                                        'postcode' => $data[22] ?? "",
                                        'country' => $data[23] ?? "",
                                        'emergency_person' => $data[24] ?? "",
                                        'emergency_relation' => $data[25] ?? "",
                                        'emergency_phone_code' => $data[26] ?? "",
                                        'emergency_contact' => $data[27] ?? "",
                                    ]);
                                    $contact_data->save();

                                    $employement_data = new EmployementData([
                                        $date_joined_int = intval($data[28]),
                                        'date_joined' => \PhpOffice\PhpSpreadsheet\Shared\Date::excelToDateTimeObject($date_joined_int)->format('Y-m-d'),
                                        'offer_letter' => $data[29] ?? "",
                                        'role_permission' => $data[30] ?? "",
                                        'report_to' => $data[31] ?? "",
                                        'position' => $data[32] ?? "",
                                        'position_grade' => $data[33] ?? "",
                                        'team' => $data[34] ?? "",
                                        'working_hours' => $data[35] ?? "",
                                        'work_location' => $data[36] ?? "",
                                        'branch_office' => $data[37] ?? "",
                                        'job_status' => $data[38] ?? "",
                                        'job_type' => $data[39] ?? "",
                                        'work_permit' => $data[40] ?? "",
                                        'visa_no' => $data[41] ?? "",
                                        $visa_issue_date_int = intval($data[42]),
                                        'visa_issue_date' => \PhpOffice\PhpSpreadsheet\Shared\Date::excelToDateTimeObject($visa_issue_date_int)->format('Y-m-d'),
                                        $visa_expired_date_int = intval($data[43]),
                                        'visa_expired_date' => \PhpOffice\PhpSpreadsheet\Shared\Date::excelToDateTimeObject($visa_expired_date_int)->format('Y-m-d'),
                                        'user_id' => $user->id,
                                    ]);
                                    $employement_data->save();

                                    $leave_data = new LeaveData([
                                        'user_id' => $user->id,
                                        'rest_day' => $data[44] ?? "",
                                        'annual_leave' => $data[45] ?? "",
                                        $al_start_from_int = intval($data[46]),
                                        'al_start_from' => \PhpOffice\PhpSpreadsheet\Shared\Date::excelToDateTimeObject($al_start_from_int)->format('Y-m-d'),
                                        $al_expired_on_int = intval($data[47]),
                                        'al_expired_on' => \PhpOffice\PhpSpreadsheet\Shared\Date::excelToDateTimeObject($al_expired_on_int)->format('Y-m-d'),
                                        'flight_allowance_currency' => $data[48] ?? "",
                                        'flight_allowance' => $data[49] ?? "",
                                        $eligible_start_from_int = intval($data[50]),
                                        'eligible_start_from' => \PhpOffice\PhpSpreadsheet\Shared\Date::excelToDateTimeObject($eligible_start_from_int)->format('Y-m-d'),
                                        $eligible_expired_on_int = intval($data[51]),
                                        'eligible_expired_on' => \PhpOffice\PhpSpreadsheet\Shared\Date::excelToDateTimeObject($eligible_expired_on_int)->format('Y-m-d'),
                                        'meals_allowance_currency ' => $data[52] ?? "",
                                        'meals_allowance ' => $data[53] ?? "",
                                        'medical_allowance_currency' => $data[54] ?? "",
                                        'medical_allowance' => $data[55] ?? "",
                                        $medical_eligible_start_from_int = intval($data[56]),
                                        'medical_eligible_start_from' => \PhpOffice\PhpSpreadsheet\Shared\Date::excelToDateTimeObject($medical_eligible_start_from_int)->format('Y-m-d'),
                                        $medical_eligible_expired_on_int = intval($data[57]),
                                        'medical_eligible_expired_on' => \PhpOffice\PhpSpreadsheet\Shared\Date::excelToDateTimeObject($medical_eligible_expired_on_int)->format('Y-m-d'),
                                    ]);
                                    $leave_data->save();

                                    $finance_data = new FinanceData([
                                        'user_id' => $user->id,
                                        'currency' => $data[58] ?? "",
                                        'beneficiary' => $data[59] ?? "",
                                        'bank' => $data[60] ?? "",
                                        'account_number' => $data[61] ?? "",
                                        'basic_salary' => $data[62] ?? "",
                                        'kpi_bonus' => $data[63] ?? "",
                                    ]);
                                    $finance_data->save();
                                }

                                $users[] = $user; // append user object to $users array
                            }
                            fclose($open);
                        }
                    } elseif ($request->file('file')->getClientOriginalExtension() == 'xlsx') {
                        $users = Excel::toArray([], $fullPath)[0];
                        array_shift($users);
                        // Insert employee data into database
                        foreach ($users as $data) {

                            $user = new User();
                            $user->full_name = $data[0] ?? "";
                            $user->short_name = $data[1] ?? "";
                            $user->login_id = $data[2] ?? "";
                            $user->password = \App\Libraries\Tokenizer::password($data[3] ?? "");
                            $user->nationality = $data[4] ?? "";
                            $user->employee_id = $data[5] ?? "";
                            $user->gender = $data[6] ?? "";
                            $dateOfBirth = intval($data[7]);
                            $user->date_of_birth = \PhpOffice\PhpSpreadsheet\Shared\Date::excelToDateTimeObject($dateOfBirth)->format('Y-m-d');
                            $user->race = $data[8] ?? "";
                            $user->religion = $data[9] ?? "";

                            $user->marital_status = $data[10] ?? "";
                            $user->ic_num = $data[11] ?? "";
                            $user->ic_copy = $data[12] ?? "";

                            $ppt_expired = intval($data[13]);
                            $user->ppt_expired = \PhpOffice\PhpSpreadsheet\Shared\Date::excelToDateTimeObject($ppt_expired)->format('Y-m-d');

                            $user->ppt_copy = $data[14] ?? "";
                            $user->photo = $data[15] ?? "";

//                                add other data
                            if ($user->save()) {
                                $contact_data = new ContactData([
                                    'phone_code' => $data[16] ?? "",
                                    'mobile_number' => $data[17] ?? "",
                                    'user_id' => $user->id,
                                    'house_number' => $data[18] ?? "",
                                    'email' => $data[19] ?? "",
                                    'address' => $data[20] ?? "",
                                    'city' => $data[21] ?? "",
                                    'postcode' => $data[22] ?? "",
                                    'country' => $data[23] ?? "",
                                    'emergency_person' => $data[24] ?? "",
                                    'emergency_relation' => $data[25] ?? "",
                                    'emergency_phone_code' => $data[26] ?? "",
                                    'emergency_contact' => $data[27] ?? "",
                                ]);
                                $contact_data->save();

                                $employement_data = new EmployementData([
                                    $date_joined_int = intval($data[28]),
                                    'date_joined' => \PhpOffice\PhpSpreadsheet\Shared\Date::excelToDateTimeObject($date_joined_int)->format('Y-m-d'),
                                    'offer_letter' => $data[29] ?? "",
                                    'role_permission' => $data[30] ?? "",
                                    'report_to' => $data[31] ?? "",
                                    'position' => $data[32] ?? "",
                                    'position_grade' => $data[33] ?? "",
                                    'team' => $data[34] ?? "",
                                    'working_hours' => $data[35] ?? "",
                                    'work_location' => $data[36] ?? "",
                                    'branch_office' => $data[37] ?? "",
                                    'job_status' => $data[38] ?? "",
                                    'job_type' => $data[39] ?? "",
                                    'work_permit' => $data[40] ?? "",
                                    'visa_no' => $data[41] ?? "",
                                    $visa_issue_date_int = intval($data[42]),
                                    'visa_issue_date' => \PhpOffice\PhpSpreadsheet\Shared\Date::excelToDateTimeObject($visa_issue_date_int)->format('Y-m-d'),
                                    $visa_expired_date_int = intval($data[43]),
                                    'visa_expired_date' => \PhpOffice\PhpSpreadsheet\Shared\Date::excelToDateTimeObject($visa_expired_date_int)->format('Y-m-d'),
                                    'user_id' => $user->id,
                                ]);
                                $employement_data->save();

                                $leave_data = new LeaveData([
                                    'user_id' => $user->id,
                                    'rest_day' => $data[44] ?? "",
                                    'annual_leave' => $data[45] ?? "",
                                    $al_start_from_int = intval($data[46]),
                                    'al_start_from' => \PhpOffice\PhpSpreadsheet\Shared\Date::excelToDateTimeObject($al_start_from_int)->format('Y-m-d'),
                                    $al_expired_on_int = intval($data[47]),
                                    'al_expired_on' => \PhpOffice\PhpSpreadsheet\Shared\Date::excelToDateTimeObject($al_expired_on_int)->format('Y-m-d'),
                                    'flight_allowance_currency' => $data[48] ?? "",
                                    'flight_allowance' => $data[49] ?? "",
                                    $eligible_start_from_int = intval($data[50]),
                                    'eligible_start_from' => \PhpOffice\PhpSpreadsheet\Shared\Date::excelToDateTimeObject($eligible_start_from_int)->format('Y-m-d'),
                                    $eligible_expired_on_int = intval($data[51]),
                                    'eligible_expired_on' => \PhpOffice\PhpSpreadsheet\Shared\Date::excelToDateTimeObject($eligible_expired_on_int)->format('Y-m-d'),
                                    'meals_allowance_currency ' => $data[52] ?? "",
                                    'meals_allowance ' => $data[53] ?? "",
                                    'medical_allowance_currency' => $data[54] ?? "",
                                    'medical_allowance' => $data[55] ?? "",
                                    $medical_eligible_start_from_int = intval($data[56]),
                                    'medical_eligible_start_from' => \PhpOffice\PhpSpreadsheet\Shared\Date::excelToDateTimeObject($medical_eligible_start_from_int)->format('Y-m-d'),
                                    $medical_eligible_expired_on_int = intval($data[57]),
                                    'medical_eligible_expired_on' => \PhpOffice\PhpSpreadsheet\Shared\Date::excelToDateTimeObject($medical_eligible_expired_on_int)->format('Y-m-d'),
                                ]);
                                $leave_data->save();

                                $finance_data = new FinanceData([
                                    'user_id' => $user->id,
                                    'currency' => $data[58] ?? "",
                                    'beneficiary' => $data[59] ?? "",
                                    'bank' => $data[60] ?? "",
                                    'account_number' => $data[61] ?? "",
                                    'basic_salary' => $data[62] ?? "",
                                    'kpi_bonus' => $data[63] ?? "",
                                ]);
                                $finance_data->save();
                            }
                        }
                    }

                    $statusCode = 200;
                    $return['status'] = 'success';
                    $return['data'] = $users;
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

    public function file_format(Request $request) {
        try {
            $statusCode = 422;
            $return = [];

            $csv_file = url('') . '/exels/hr-import-format.csv';
            $excel_file = url('') . '/exels/hr-import-format.xlsx';

            $statusCode = 200;
            $return['status'] = 'success';
            $return['csv_file'] = $csv_file;
            $return['excel_file'] = $excel_file;
            return $this->response($return, $statusCode);
        } catch (\Throwable $ex) {
            return $this->error(['status' => "error", 'main_error_message' => $ex->getMessage()]);
        }
    }

}
