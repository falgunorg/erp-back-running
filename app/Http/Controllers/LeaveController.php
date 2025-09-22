<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Leave;
use Carbon\Carbon;
use Illuminate\Support\Facades\Validator;
use App\Models\User;
use App\Models\Holiday;
use Maatwebsite\Excel\Facades\Excel;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Log;
use App\Models\Department;
use App\Models\Designation;

class LeaveController extends Controller {

    public function my_leaves(Request $request) {
        try {
            $statusCode = 422;
            $response = ['status' => 'error'];

            // Retrieve inputs with validation
            $leaveType = $request->input('leave_type');
            $fromDate = $request->input('from_date'); // Adjusted to match front-end naming
            $toDate = $request->input('to_date');    // Adjusted to match front-end naming
            $status = $request->input('status');
            $userId = $request->user->id;

            // Initialize query
            $query = Leave::query()->where('user_id', $userId)->orderBy('created_at', 'desc');

            // Apply filters
            if ($leaveType) {
                $query->where('leave_type', $leaveType);
            }

            if ($fromDate && $toDate) {
                // Adjust toDate for inclusive filtering
                $adjustedToDate = date('Y-m-d', strtotime($toDate . ' +1 day'));
                $query->whereBetween('start_date', [$fromDate, $adjustedToDate]);
            } elseif ($fromDate) {
                $query->whereDate('start_date', '>=', $fromDate);
            } elseif ($toDate) {
                $adjustedToDate = date('Y-m-d', strtotime($toDate . ' +1 day'));
                $query->whereDate('end_date', '<=', $adjustedToDate);
            }

            if ($status) {
                $query->where('status', $status);
            }

            // Fetch paginated results
            $leaves = $query->paginate(20);

            $statusCode = 200;
            $response = [
                'status' => 'success',
                'leaves' => $leaves,
            ];

            return response()->json($response, $statusCode);
        } catch (\Throwable $ex) {
            return response()->json([
                        'status' => 'error',
                        'message' => $ex->getMessage(),
                            ], 500);
        }
    }

    public function store(Request $request) {
        try {
            $statusCode = 422;
            $return = [];
            $user_id = $request->user->id;

            $user = \App\Models\User::find($user_id);

            // Validation
            $validator = Validator::make($request->all(), [
                'leave_type' => 'required|in:Sick Leave,Casual Leave,Earn Leave,Maternity Leave',
                'start_date' => 'required|date',
                'end_date' => 'required|date|after_or_equal:start_date',
                'total_days' => 'required|integer',
                'reason' => 'nullable|string',
            ]);

            if ($validator->fails()) {
                $return['errors'] = $validator->errors();
                $statusCode = 422;
            } else {
                // Check if the user already has a leave on the same day(s)
                $start_date = $request->input('start_date');
                $end_date = $request->input('end_date');

                $adjusted_end_date = Carbon::parse($end_date)->endOfDay();

                $existingLeave = Leave::where('user_id', $user->id)
                        ->where(function ($query) use ($start_date, $adjusted_end_date) {
                            $query->whereBetween('start_date', [$start_date, $adjusted_end_date])
                                    ->orWhereBetween('end_date', [$start_date, $adjusted_end_date])
                                    ->orWhereRaw('? BETWEEN start_date AND end_date', [$start_date])
                                    ->orWhereRaw('? BETWEEN start_date AND end_date', [$adjusted_end_date]);
                        })
                        ->exists();

                if ($existingLeave) {
                    $return['status'] = 'error';
                    $return['message'] = 'You already have a leave scheduled during this period.';
                    $statusCode = 409;
                } else {
                    // Create the new leave
                    $leave = new Leave;
                    $leave->user_id = $user->id;
                    $leave->leave_type = $request->input('leave_type');
                    $leave->start_date = $start_date;
                    $leave->end_date = $end_date;
                    $leave->total_days = $request->input('total_days');
                    $leave->status = 'Pending';
                    $leave->reason = $request->input('reason');
                    $leave->company = $user->company;
                    $leave->department = $user->department;
                    $leave->designation = $user->designation;
                    $leave->save();

                    $receiver = $this->getLeaveApprovalReceiver($user);

                    $notification = new \App\Models\Notification;
                    $notification->title = "Leave Appliction Placed by " . $user->full_name;
                    $notification->receiver = $receiver;
                    $notification->url = "/leaves/";
                    $notification->description = "Please Take Necessary Action";
                    $notification->is_read = 0;
                    $notification->save();

                    $return['data'] = $leave;
                    $statusCode = 200;
                    $return['status'] = 'success';
                }
            }

            return $this->response($return, $statusCode);
        } catch (\Throwable $ex) {
            return $this->error(['status' => "error", 'main_error_message' => $ex->getMessage()]);
        }
    }

    /**
     * Get the receiver for leave approval notifications
     */
    private function getLeaveApprovalReceiver($user) {
        $departmentApprovalMap = [
            'Accounts & Finance' => ['General Manager' => [9, 12], 'default' => [4, 26]],
            'Audit' => ['Assistant Manager' => [9, 12], 'default' => [5, 2]],
            'Commercial' => ['Asst. General Manager' => [9, 12], 'default' => [6, 23]],
            'HR' => ['Manager' => [9, 29], 'default' => [7, 1]],
            'Administration' => ['Manager' => [9, 12], 'default' => [8, 1]],
            'Management' => ['Managing Director' => null, 'default' => [9, 12]],
            'IT' => ['Manager' => [9, 12], 'default' => [10, 1]],
            'Marketing' => ['Deputy General Manager' => [9, 12], 'default' => [11, 25]],
            'Production' => ['Manager' => [9, 29], 'default' => [12, 1]],
            'Electric' => ['Manager' => [9, 29], 'default' => [13, 1]],
            'Merchandising' => ['Deputy General Manager' => [9, 12], 'default' => [16, 25]],
            'Sample' => ['Manager' => [9, 29], 'default' => [18, 1]],
            'Development' => ['Manager' => [9, 12], 'default' => [19, 1]],
            'Finishing' => ['Manager' => [9, 29], 'default' => [20, 1]],
            'Store' => ['Manager' => [9, 29], 'default' => [21, 1]],
            'Cutting' => ['Manager' => [9, 29], 'default' => [22, 1]],
            'Sewing' => ['Manager' => [9, 29], 'default' => [23, 1]],
            'Embroidery' => ['Manager' => [9, 29], 'default' => [24, 1]],
            'Planing' => ['Manager' => [9, 29], 'default' => [25, 1]],
            'Maintenance' => ['Manager' => [9, 29], 'default' => [26, 1]],
            'Purchase' => ['Manager' => [9, 29], 'default' => [27, 1]],
            'Washing' => ['Manager' => [9, 29], 'default' => [28, 1]],
        ];

        $departmentTitle = Department::find($user->department)?->title;
        $designationTitle = Designation::find($user->designation)?->title;

        if (isset($departmentApprovalMap[$departmentTitle])) {
            $approver = $departmentApprovalMap[$departmentTitle];

            if (isset($approver[$designationTitle])) {
                [$dep, $des] = $approver[$designationTitle];
            } else {
                [$dep, $des] = $approver['default'];
            }

            return User::where(['department' => $dep, 'designation' => $des, 'company' => $user->company])->first()?->id ?? 0;
        }

        return 0;
    }

    public function show(Request $request) {
        try {
            $statusCode = 422;
            $return = [];

            $id = $request->input('id');
            $leave = Leave::find($id);

            if ($leave) {
                $return['data'] = $leave;
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

            $user_id = $request->user->id; // Corrected method to retrieve user ID
            $id = $request->input('id');
            $leave = Leave::find($id);

            if (!$leave) {
                $return['status'] = 'error';
                $return['message'] = 'Leave not found';
                return $this->response($return, $statusCode);
            }

            // Ensure only the user who owns the leave and when status is 'Pending' can update
            if ($leave->user_id !== $user_id || $leave->status !== 'Pending') {
                $return['status'] = 'error';
                $return['message'] = 'Unauthorized or Leave status is not Pending';
                return $this->response($return, $statusCode);
            }

            $validator = Validator::make($request->all(), [
                'leave_type' => 'required|in:Sick Leave,Casual Leave,Earn Leave,Maternity Leave',
                'start_date' => 'required|date',
                'end_date' => 'required|date|after_or_equal:start_date',
                'total_days' => 'required|integer',
                'reason' => 'nullable|string',
            ]);

            if ($validator->fails()) {
                $return['errors'] = $validator->errors();
            } else {
                $start_date = $request->input('start_date');
                $end_date = Carbon::parse($request->input('end_date'))->endOfDay();

                // Check for overlapping leaves
                $existingLeave = Leave::where('user_id', $user_id)
                        ->where('id', '!=', $leave->id) // Exclude the current leave
                        ->where(function ($query) use ($start_date, $end_date) {
                            $query->whereBetween('start_date', [$start_date, $end_date])
                                    ->orWhereBetween('end_date', [$start_date, $end_date])
                                    ->orWhereRaw('? BETWEEN start_date AND end_date', [$start_date])
                                    ->orWhereRaw('? BETWEEN start_date AND end_date', [$end_date]);
                        })
                        ->exists();

                if ($existingLeave) {
                    $return['status'] = 'error';
                    $return['message'] = 'Leave dates overlap with an existing leave';
                    return $this->response($return, $statusCode);
                }

                // Update only the allowed fields
                $leave->fill($request->only(['leave_type', 'start_date', 'end_date', 'total_days', 'reason']));
                $leave->save();

                $return['data'] = $leave;
                $statusCode = 200;
                $return['status'] = 'success';
            }

            return $this->response($return, $statusCode);
        } catch (\Throwable $ex) {
            return $this->error(['status' => "error", 'main_error_message' => $ex->getMessage()]);
        }
    }

    public function update_leave_type(Request $request) {
        try {
            $statusCode = 422;
            $return = [];
            $id = $request->input('id');
            $leave = Leave::find($id);

            if (!$leave) {
                $return['status'] = 'error';
                $return['message'] = 'Leave not found';
                return $this->response($return, $statusCode);
            }



            $validator = Validator::make($request->all(), [
                'leave_type' => 'required|in:Sick Leave,Casual Leave,Earn Leave,Maternity Leave',
                'start_date' => 'required|date',
                'end_date' => 'required|date|after_or_equal:start_date',
                'total_days' => 'required|integer',
                'reason' => 'nullable|string',
            ]);

            if ($validator->fails()) {
                $return['errors'] = $validator->errors();
            } else {
                // Update only the allowed fields
                $leave->fill($request->only(['leave_type']));
                $leave->save();

                $return['data'] = $leave;
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

            $user_id = $request->user->id; // Corrected method to retrieve user ID
            $id = $request->input('id');
            $leave = Leave::find($id);

            if (!$leave) {
                $return['status'] = 'error';
                $return['message'] = 'Leave not found';
                return $this->response($return, $statusCode);
            }

            // Ensure only the user who owns the leave and when status is 'Pending' can delete
            if ($leave->user_id !== $user_id || $leave->status !== 'Pending') {
                $return['status'] = 'error';
                $return['message'] = 'Unauthorized or Leave status is not Pending';
                return $this->response($return, $statusCode);
            }

            // Delete the leave
            $leave->delete();
            $return['status'] = 'success';
            $statusCode = 200;

            return $this->response($return, $statusCode);
        } catch (\Throwable $ex) {
            return $this->error(['status' => "error", 'main_error_message' => $ex->getMessage()]);
        }
    }

    public function toggleStaus(Request $request) {
        try {
            $status = $request->input('status'); // New status to toggle to
            $userId = $request->user->id; // Get the logged-in user's ID
            $id = $request->input('id');
            $item = Leave::findOrFail($id); // Retrieve the item by ID
            // Validate the status change
            if (!in_array($status, ['Recommended', 'Approved', 'Rejected'])) {
                return response()->json(['status' => 'error', 'message' => 'Invalid status'], 400);
            }

            // Handle status transitions
            switch ($status) {
                case 'Recommended':
                    $item->status = 'Recommended';
                    $item->recommended_at = now();
                    $item->recommended_by = $userId;
                    break;

                case 'Approved':
                    $item->status = 'Approved';
                    $item->approved_at = now();
                    $item->approved_by = $userId;
                    break;

                case 'Rejected':
                    $item->status = 'Rejected';
                    $item->rejected_at = now();
                    $item->rejected_by = $userId;
                    break;
            }

            if ($item->save()) {
                $user = User::find($userId);
                $notification = new \App\Models\Notification;
                $notification->title = "Your Appliction " . $item->status . " by " . $user->full_name;
                $notification->receiver = $item->user_id;
                $notification->url = "/leaves/";
                $notification->description = "Please Take Necessary Action";
                $notification->is_read = 0;
                $notification->save();
            }






            // Return success response
            return response()->json(['status' => 'success', 'data' => $item]);
        } catch (\Exception $e) {
            // Handle any exceptions
            return response()->json(['status' => 'error', 'message' => $e->getMessage()], 500);
        }
    }

    public function my_leaves_summary_yearly(Request $request) {
        try {
            $statusCode = 422;
            $response = ['status' => 'error'];
            $userId = $request->user->id;
            $year = $request->input('year');

            // Retrieve approved leaves for the specified user and year
            $leaves = Leave::where('user_id', $userId)
                    ->whereYear('start_date', $year)
                    ->where('status', 'Approved')
                    ->get();

            // Group leaves by leave_type and calculate total_days for each type
            $leaveCounts = $leaves->groupBy('leave_type')->map(function ($group) {
                return $group->sum('total_days');
            });

            // Calculate total days across all leave types
            $totalLeaves = $leaveCounts->sum();

            $statusCode = 200;
            $response = [
                'status' => 'success',
                'total_leaves' => $totalLeaves,
                'leave_counts' => $leaveCounts, // Array with leave_type and total_days
            ];

            return response()->json($response, $statusCode);
        } catch (\Throwable $ex) {
            return response()->json([
                        'status' => 'error',
                        'message' => $ex->getMessage(),
                            ], 500);
        }
    }

    public function my_leaves_summary_overview(Request $request) {
        try {
            $statusCode = 422;
            $response = ['status' => 'error'];
            $userId = $request->user->id;

            // Get current month and year
            $currentMonth = now()->month;
            $currentYear = now()->year;

            // Fetch approved leaves for this month and year
            $leavesThisMonth = Leave::where('user_id', $userId)
                    ->whereMonth('start_date', $currentMonth)
                    ->whereYear('start_date', $currentYear)
                    ->where('status', 'Approved')
                    ->sum('total_days');

            $leavesThisYear = Leave::where('user_id', $userId)
                    ->whereYear('start_date', $currentYear)
                    ->where('status', 'Approved')
                    ->sum('total_days');

            // Fetch pending leaves for this month
            $pendingLeavesThisMonth = Leave::where('user_id', $userId)
                    ->whereMonth('start_date', $currentMonth)
                    ->whereYear('start_date', $currentYear)
                    ->where('status', 'Pending')
                    ->count();

            // Fetch rejected leaves for this year
            $rejectedLeavesThisYear = Leave::where('user_id', $userId)
                    ->whereYear('start_date', $currentYear)
                    ->where('status', 'Rejected')
                    ->count();

            $statusCode = 200;
            $response = [
                'status' => 'success',
                'data' => [
                    'leaves_this_month' => $leavesThisMonth,
                    'leaves_this_year' => $leavesThisYear,
                    'pending_leaves_this_month' => $pendingLeavesThisMonth,
                    'rejected_leaves_this_year' => $rejectedLeavesThisYear,
                ],
            ];

            return response()->json($response, $statusCode);
        } catch (\Throwable $ex) {
            return response()->json([
                        'status' => 'error',
                        'message' => $ex->getMessage(),
                            ], 500);
        }
    }

    public function my_leaves_calendar(Request $request) {
        try {
            $statusCode = 422;
            $return = [];
            $user_id = $request->user->id;
            $year = $request->input('year');

            // Get all approved leaves for the specified year, including leaves that span across the year boundary
            $leaves = Leave::where('user_id', $user_id)
                    ->where(function ($query) use ($year) {
                        $query->whereYear('start_date', $year)
                                ->orWhereYear('end_date', $year);
                    })
                    ->where('status', 'Approved')
                    ->get();

            // Initialize monthly leave data
            $monthlyLeaves = [];
            foreach ($leaves as $leave) {
                $startDate = Carbon::parse($leave->start_date);
                $endDate = Carbon::parse($leave->end_date);

                // For each leave, loop through the date range from start to end date
                while ($startDate->lte($endDate)) {
                    $date = $startDate->format('Y-m-d');

                    // Add this date to the leave's data for later processing
                    $monthlyLeaves[$startDate->month][] = [
                        'date' => $date,
                        'type' => $leave->leave_type, // Assuming `leave_type` is the type of leave (Sick, Casual, etc.)
                        'leave_start_date' => $leave->start_date,
                        'leave_end_date' => $leave->end_date
                    ];

                    // Move to the next day
                    $startDate->addDay();
                }
            }

            // Now we process the data into a monthly structure
            $data = [];
            for ($month = 1; $month <= 12; $month++) {
                $totalDays = Carbon::createFromDate($year, $month, 1)->daysInMonth;
                $dateList = [];

                for ($i = 1; $i <= $totalDays; $i++) {
                    $date = Carbon::createFromDate($year, $month, $i)->format('Y-m-d');
                    $leave = isset($monthlyLeaves[$month]) ? collect($monthlyLeaves[$month])->firstWhere('date', $date) : null;

                    // Push the date and leave type (if any) into the dateList
                    $dateList[] = [
                        'date' => $date,
                        'type' => $leave ? $leave['type'] : '' // Leave type or empty if no leave for that date
                    ];
                }

                $data[] = [
                    'month' => Carbon::createFromDate($year, $month, 1)->format('F'),
                    'total_days' => $totalDays,
                    'date_list' => $dateList
                ];
            }

            // Return the result
            return response()->json([
                        'status' => 'success',
                        'data' => $data
                            ], 200);
        } catch (\Throwable $ex) {
            return response()->json([
                        'status' => 'error',
                        'message' => $ex->getMessage()
                            ], 500);
        }
    }

//    Actions Functions
    public function admin_summary(Request $request) {
        try {
            $statusCode = 422;
            $response = ['status' => 'error'];
            $user = User::find($request->user->id);

            // Get today's date and tomorrow's date
            $today = now()->toDateString();
            $tomorrow = now()->addDay()->toDateString();

            // Get users on leave today
            $today_leaves = Leave::whereDate('start_date', '<=', $today)
                    ->whereDate('end_date', '>=', $today)
                    ->where('company', $user->company)
                    ->get();

            // Get users on leave tomorrow
            $tomorrow_leaves = Leave::whereDate('start_date', '<=', $tomorrow)
                    ->whereDate('end_date', '>=', $tomorrow)
                    ->where('company', $user->company)
                    ->get();

            // Format today's leave summary
            $todaySummary = $today_leaves->map(function ($leave) {
                return [
                    'user_name' => $leave->user->full_name,
                    'staff_id' => $leave->user->staff_id,
                    'leave_type' => $leave->leave_type,
                    'start_date' => $leave->start_date,
                    'end_date' => $leave->end_date,
                    'total_days' => $leave->total_days,
                ];
            });

            // Format tomorrow's leave summary
            $tomorrowSummary = $tomorrow_leaves->map(function ($leave) {
                return [
                    'user_name' => $leave->user->full_name,
                    'staff_id' => $leave->user->staff_id,
                    'leave_type' => $leave->leave_type,
                    'start_date' => $leave->start_date,
                    'end_date' => $leave->end_date,
                    'total_days' => $leave->total_days,
                ];
            });

            $response = [
                'status' => 'success',
                'data' => [
                    'today' => $todaySummary,
                    'tomorrow' => $tomorrowSummary,
                ],
            ];
            $statusCode = 200;

            return response()->json($response, $statusCode);
        } catch (\Throwable $ex) {
            return response()->json([
                        'status' => 'error',
                        'message' => $ex->getMessage(),
                            ], 500);
        }
    }

    public function to_do_action(Request $request) {
        try {
            $statusCode = 422;
            $response = ['status' => 'error'];

            // Retrieve inputs with validation
            $department_title = $request->input('department');
            $designation_title = $request->input('designation');

            $leaveType = $request->input('leave_type');
            $fromDate = $request->input('from_date');
            $toDate = $request->input('to_date');
            $status = $request->input('status');
            $user = \App\Models\User::find($request->user->id);

            // Initialize query
            $query = Leave::query();
            if ($department_title == 'IT' && $designation_title == 'Manager') {
                $query->where('department', $user->department)
                        ->where('status', 'Pending')
                        ->where('user_id', '!=', $user->id);
            } elseif ($department_title == 'Accounts & Finance' && $designation_title == 'General Manager') {
                $query->where('company', $user->company)
                        ->where('department', $user->department)
                        ->where('status', 'Pending')
                        ->where('user_id', '!=', $user->id);
            } elseif ($department_title == 'Purchase' && $designation_title == 'Assistant Manager') {
                $query->where('department', $user->department)
                        ->where('status', 'Pending')
                        ->where('user_id', '!=', $user->id);
            } elseif ($department_title == 'Merchandising' && $designation_title == 'Deputy General Manager') {
                $query->where('company', $user->company)
                        ->where('department', $user->department)
                        ->where('status', 'Pending')
                        ->where('user_id', '!=', $user->id);
            } elseif ($department_title == 'Commercial' && $designation_title == 'Asst. General Manager') {
                $query->where('company', $user->company)
                        ->where('department', $user->department)
                        ->where('status', 'Pending')
                        ->where('user_id', '!=', $user->id);
            } elseif ($department_title == 'Administration' && $designation_title == 'Manager') {
                $query->where(function ($q) use ($user) {
                    $q->where(function ($subQuery) use ($user) {
                        // Pending and Recommended items from the user's department
                        $subQuery->where('department', $user->department)
                                ->whereIn('status', ['Pending', 'Recommended'])
                                ->where('user_id', '!=', $user->id);
                    })->orWhere(function ($subQuery) use ($user) {
                        // Recommended items from all departments in the company
                        $subQuery->where('company', $user->company)
                                ->where('status', 'Recommended');
                    });
                });
            } elseif ($department_title == 'HR' && $designation_title == 'Manager') {
                $query->where('company', $user->company)
                        ->where(function ($q) use ($user) {
                            // Include only Recommended items from department 10
                            $q->where(function ($subQuery) {
                                        $subQuery->where('department', 10)
                                                ->where('status', 'Recommended');
                                    })
                                    // Include Pending and Recommended items from other departments, but exclude user's own Pending items
                                    ->orWhere(function ($subQuery) use ($user) {
                                        $subQuery->where('department', '!=', 10)
                                                ->where(function ($statusQuery) use ($user) {
                                                    $statusQuery->where('status', 'Recommended') // Include Recommended items
                                                            ->orWhere(function ($pendingQuery) use ($user) {
                                                                $pendingQuery->where('status', 'Pending') // Include Pending items
                                                                        ->where('user_id', '!=', $user->id); // Exclude user's own Pending items
                                                            });
                                                });
                                    });
                        });
            } elseif ($department_title == 'Planing' && $designation_title == 'Manager') {
                $query->where('company', $user->company)
                        ->where('status', 'Pending')
                        ->where('user_id', '!=', $user->id);
            } elseif ($department_title == 'Management' && $designation_title == 'Managing Director') {
                $query->where('status', 'Pending');
            } else {
                $query = [];
            }

            // Apply filters
            if ($leaveType) {
                $query->where('leave_type', $leaveType);
            }
            if ($fromDate && $toDate) {
                // Adjust toDate for inclusive filtering
                $adjustedToDate = date('Y-m-d', strtotime($toDate . ' +1 day'));
                $query->whereBetween('start_date', [$fromDate, $adjustedToDate]);
            } elseif ($fromDate) {
                $query->whereDate('start_date', '>=', $fromDate);
            } elseif ($toDate) {
                $adjustedToDate = date('Y-m-d', strtotime($toDate . ' +1 day'));
                $query->whereDate('end_date', '<=', $adjustedToDate);
            }

            if ($status) {
                $query->where('status', $status);
            }
            // Final ordering and pagination
            $leaves = $query->orderBy('created_at', 'desc')
                    ->paginate(40);

            foreach ($leaves as $leave) {
                $user = \App\Models\User::where('id', $leave->user_id)->first();
                $department = \App\Models\Department::where('id', $leave->department)->first();
                $leave->user_name = $user->full_name;
                $leave->user_staff_id = $user->staff_id;
                $leave->user_department = $department->title;
                $recommanded_user = \App\Models\User::where('id', $leave->recommended_by)->first();

                if ($recommanded_user) {
                    $leave->recommanded_by_name = $recommanded_user->full_name;
                } else {
                    $leave->recommanded_by_name = "Not Recommanded Yet";
                }
            }


            $statusCode = 200;
            $response = [
                'status' => 'success',
                'leaves' => $leaves,
            ];

            return response()->json($response, $statusCode);
        } catch (\Throwable $ex) {
            return response()->json([
                        'status' => 'error',
                        'message' => $ex->getMessage(),
                            ], 500);
        }
    }

    public function admin_leaves_actions(Request $request) {
        try {
            $statusCode = 422;
            $response = ['status' => 'error'];

            $leaveType = $request->input('leave_type');
            $fromDate = $request->input('from_date');
            $toDate = $request->input('to_date');
            $status = $request->input('status');
            $employee_id = $request->input('employee_id');
            $user = \App\Models\User::find($request->user->id);

            // Initialize query
            $query = Leave::query();

            $query->where(function ($q) use ($user) {
                $q->where(function ($subQuery) use ($user) {
                    $subQuery->where('department', $user->department)
                            ->whereIn('status', ['Pending', 'Recommended', 'Approved', 'Rejected'])
                            ->where(function ($statusCheck) use ($user) {
                                $statusCheck->where('status', '!=', 'Pending')
                                        ->orWhere('user_id', '!=', $user->id);
                            });
                })->orWhere(function ($subQuery) use ($user) {
                    // Recommended and Approved items from all departments in the company
                    $subQuery->where('company', $user->company)
                            ->whereIn('status', ['Recommended', 'Approved']);
                });
            });
            if ($employee_id) {
                $query->where('user_id', $employee_id);
            }
            // Apply filters
            if ($leaveType) {
                $query->where('leave_type', $leaveType);
            }
            if ($fromDate && $toDate) {
                // Adjust toDate for inclusive filtering
                $adjustedToDate = date('Y-m-d', strtotime($toDate . ' +1 day'));
                $query->whereBetween('start_date', [$fromDate, $adjustedToDate]);
            } elseif ($fromDate) {
                $query->whereDate('start_date', '>=', $fromDate);
            } elseif ($toDate) {
                $adjustedToDate = date('Y-m-d', strtotime($toDate . ' +1 day'));
                $query->whereDate('end_date', '<=', $adjustedToDate);
            }

            if ($status) {
                $query->where('status', $status);
            }
            // Final ordering and pagination
            $leaves = $query->orderBy('created_at', 'desc')
                    ->paginate(50);

            foreach ($leaves as $leave) {
                $user = \App\Models\User::where('id', $leave->user_id)->first();
                $department = \App\Models\Department::where('id', $leave->department)->first();
                $leave->user_name = $user->full_name;
                $leave->user_staff_id = $user->staff_id;
                $leave->user_department = $department->title;
                $recommanded_user = \App\Models\User::where('id', $leave->recommended_by)->first();

                if ($recommanded_user) {
                    $leave->recommanded_by_name = $recommanded_user->full_name;
                } else {
                    $leave->recommanded_by_name = "Not Recommanded Yet";
                }
            }


            $statusCode = 200;
            $response = [
                'status' => 'success',
                'leaves' => $leaves,
            ];

            return response()->json($response, $statusCode);
        } catch (\Throwable $ex) {
            return response()->json([
                        'status' => 'error',
                        'message' => $ex->getMessage(),
                            ], 500);
        }
    }

    public function get_admin_report_monthly(Request $request) {
        try {
            $statusCode = 422;
            $data = []; // Initialize the response data array
            $year = $request->input('year');
            $month = $request->input('month');
            $employee_id = $request->input('employee_id');

            $loggedInUser = User::find($request->user->id);
            // Query users based on the employee_id if provided
            $users = User::where('company', $loggedInUser->company);
            // Add condition for employee_id if provided
            $users = $users->when($employee_id, function ($query) use ($employee_id) {
                return $query->where('id', $employee_id);
            });

            // Filter users who have leaves in the specified month
            $usersWithLeaves = $users->whereHas('leaves', function ($query) use ($year, $month) {
                        $query->where('status', 'Approved')
                                ->where(function ($query) use ($year, $month) {
                                    $query->whereYear('start_date', $year)
                                            ->whereMonth('start_date', $month)
                                            ->orWhere(function ($query) use ($year, $month) {
                                                $query->whereYear('end_date', $year)
                                                        ->whereMonth('end_date', $month);
                                            });
                                });
                    })->get();

            foreach ($usersWithLeaves as $user) {
                $leaves = Leave::where('user_id', $user->id)
                        ->where('status', 'Approved')
                        ->where(function ($query) use ($year, $month) {
                            $query->whereYear('start_date', $year)
                                    ->whereMonth('start_date', $month)
                                    ->orWhere(function ($query) use ($year, $month) {
                                        $query->whereYear('end_date', $year)
                                                ->whereMonth('end_date', $month);
                                    });
                        })
                        ->get();

                // Create a daily leave tracker for the month
                $daysInMonth = cal_days_in_month(CAL_GREGORIAN, $month, $year);
                $dailyLeaveData = array_fill(1, $daysInMonth, 0);

                foreach ($leaves as $leave) {
                    // Ensure start_date and end_date are Carbon instances
                    $startDate = Carbon::parse($leave->start_date)->startOfDay();
                    $endDate = Carbon::parse($leave->end_date)->endOfDay();

                    // Adjust the start and end dates if necessary
                    $startDate = max($startDate, Carbon::create($year, $month, 1));
                    $endDate = min($endDate, Carbon::create($year, $month, $daysInMonth));

                    $leaveDays = $startDate->diffInDays($endDate) + 1;

                    // Track leave days for each day in the range
                    for ($day = $startDate->day; $day <= $endDate->day; $day++) {
                        $dailyLeaveData[$day]++;
                    }
                }

                // Add the data to the response
                $data[] = [
                    'user' => $user->full_name,
                    'staff_id' => $user->staff_id,
                    'daysInMonth' => $daysInMonth,
                    'daily_leave_data' => $dailyLeaveData,
                    'total' => array_sum($dailyLeaveData),
                ];
            }

            return response()->json([
                        'status' => 'success',
                        'data' => $data,
                            ], 200);
        } catch (Exception $e) {
            return response()->json([
                        'status' => 'error',
                        'message' => $e->getMessage(),
                            ], $statusCode);
        }
    }

    public function get_admin_report_yearly(Request $request) {
        try {
            $statusCode = 422;
            $data = []; // Initialize the response data array

            $year = $request->input('year');
            $employee_id = $request->input('employee_id');

            // Query users based on the employee_id if provided
            $loggedInUser = User::find($request->user->id);
            // Query users based on the employee_id if provided
            $users = User::where('company', $loggedInUser->company);
            // Add condition for employee_id if provided
            if ($employee_id) {
                $users = $users->where('id', $employee_id);
            }

            // Fetch users who have leave records in the specified year
            $users = $users->whereHas('leaves', function ($query) use ($year) {
                        $query->whereYear('start_date', $year)
                                ->orWhereYear('end_date', $year)
                                ->where('status', 'Approved');
                    })->get();  // Get users with approved leave records
            // Filter out users who don't have approved leave records in the given year
            $usersWithLeaves = $users->filter(function ($user) use ($year) {
                return $user->leaves()->whereYear('start_date', $year)->where('status', 'Approved')->exists();
            });

            foreach ($usersWithLeaves as $user) {
                $leaves = Leave::where('user_id', $user->id)
                        ->where(function ($query) use ($year) {
                            $query->whereYear('start_date', $year)
                                    ->orWhereYear('end_date', $year);
                        })
                        ->where('status', 'Approved')
                        ->get();

                // Create a monthly leave tracker for the year (12 months, initialized to 0)
                $monthlyLeaveData = array_fill(1, 12, 0);

                foreach ($leaves as $leave) {
                    // Ensure start_date and end_date are Carbon instances
                    $start = Carbon::parse($leave->start_date);
                    $end = Carbon::parse($leave->end_date);

                    // If the leave spans across multiple months, calculate days correctly
                    $startMonth = max($start->month, 1);  // Ensure start from January
                    $endMonth = min($end->month, 12);     // Ensure end at December
                    // Loop through each month the leave spans
                    for ($month = $startMonth; $month <= $endMonth; $month++) {
                        // Get the first day and last day of the month
                        $monthStart = Carbon::create($year, $month, 1);
                        $monthEnd = $monthStart->copy()->endOfMonth();

                        // Calculate the overlap of the leave with the month
                        $overlapStart = max($start, $monthStart);
                        $overlapEnd = min($end, $monthEnd);

                        // If there's an overlap in the month, count the days
                        if ($overlapStart <= $overlapEnd) {
                            $daysInMonth = $overlapStart->diffInDays($overlapEnd) + 1; // Include the last day
                            $monthlyLeaveData[$month] += $daysInMonth;
                        }
                    }
                }

                // Add the data to the response
                $data[] = [
                    'user' => $user->full_name,
                    'id' => $user->id,
                    'staff_id' => $user->staff_id,
                    'monthly_leave_data' => $monthlyLeaveData,
                    'total' => array_sum($monthlyLeaveData),
                ];
            }

            return response()->json([
                        'status' => 'success',
                        'data' => $data,
                            ], 200);
        } catch (Exception $e) {
            return response()->json([
                        'status' => 'error',
                        'message' => $e->getMessage(),
                            ], $statusCode);
        }
    }

// FILE READING AND WRITING FORMULA 
// Now all file read and pushed into dataset will work As CronJob php artisan attendance:make-single-csv
    public function make_single_csv_from_multiple(Request $request) {
        $dataFile = public_path('csv/dataset.csv'); // Destination file
        $attendanceDirectory = public_path('excels'); // Source CSV folder

        if (!is_dir($attendanceDirectory)) {
            Log::error("Attendance directory not found: $attendanceDirectory");
            return response()->json(['status' => 'error', 'message' => 'Attendance directory not found'], 404);
        }

        $files = scandir($attendanceDirectory);
        $attendanceFiles = array_filter($files, fn($file) => preg_match("/^\d+\.csv$/", $file));

        if (empty($attendanceFiles)) {
            return response()->json(['status' => 'error', 'message' => 'No attendance files found'], 404);
        }

        $allRows = collect();
        $existingData = [];

        // Step 1: Read existing dataset.csv to prevent duplicate entries per day
        if (File::exists($dataFile)) {
            if (($handle = fopen($dataFile, 'r')) !== false) {
                $headers = fgetcsv($handle);
                while (($row = fgetcsv($handle)) !== false) {
                    $rowAssoc = array_combine($headers, $row);
                    $acNo = $rowAssoc['Ac-No'];
                    $dateOnly = Carbon::createFromFormat('d/m/Y H:i:s', $rowAssoc['formattedDate'])->format('d/m/Y'); // Extract only date part
                    $existingData["$acNo|$dateOnly"] = true; // Store Ac-No & Date to prevent duplicates
                }
                fclose($handle);
            }
        }

        // Step 2: Process each CSV file
        foreach ($attendanceFiles as $fileName) {
            $filePath = $attendanceDirectory . '/' . $fileName;
            Log::info("Processing file: $filePath");

            if (($handle = fopen($filePath, 'r')) !== false) {
                $headers = fgetcsv($handle);
                Log::info("Headers in $fileName: " . implode(', ', $headers));

                while (($row = fgetcsv($handle)) !== false) {
                    $row = array_combine($headers, $row);

                    if (!isset($row['sTime']) || !isset($row['Ac-No'])) {
                        Log::warning("Missing required columns in file: $fileName");
                        continue;
                    }

                    try {
                        $fullDate = Carbon::createFromFormat('d/m/Y h:i A', $row['sTime']);
                        $dateOnly = $fullDate->format('d/m/Y'); // Extract only the date
                        $row['formattedDate'] = $fullDate->format('d/m/Y H:i:s'); // Standardized format
                    } catch (\Exception $e) {
                        Log::error("Invalid date format in $fileName: " . $row['sTime']);
                        continue;
                    }

                    $acNo = $row['Ac-No'];
                    $uniqueKey = "$acNo|$dateOnly";

                    // Step 3: Check for duplicate entry per Ac-No per day
                    if (!isset($existingData[$uniqueKey])) {
                        $allRows->push($row);
                        $existingData[$uniqueKey] = true; // Mark as existing
                    } else {
                        Log::info("Duplicate entry for Ac-No $acNo on $dateOnly skipped.");
                    }
                }
                fclose($handle);
            } else {
                Log::error("Could not open file: $filePath");
            }
        }

        // Step 4: Append new unique data to dataset.csv
        if ($allRows->isNotEmpty()) {
            $fileExists = File::exists($dataFile);
            $fp = fopen($dataFile, 'a');

            if (!$fileExists) {
                fputcsv($fp, array_keys($allRows->first())); // Write headers if file is new
            }

            foreach ($allRows as $row) {
                fputcsv($fp, $row);
            }
            fclose($fp);

            Log::info("Data successfully written to dataset.csv");
        } else {
            Log::warning("No new data found to append.");
        }

        return response()->json(['status' => 'success', 'message' => 'Data merged successfully!']);
    }

    public function getAttendanceData(Request $request) {
        try {
            $company_id = $request->input('company_id');
            $fetchingDate = $request->input('request_date', now()->format('Y-m-d'));
            $formattedDate = Carbon::parse($fetchingDate)->format('d/m/Y');

            $filePath = public_path('csv/dataset.csv');
            if (!file_exists($filePath)) {
                return response()->json(['status' => 'error', 'message' => 'Attendance file not found'], 404);
            }

            // Fetch only required fields
            $users = User::select('staff_id', 'full_name')
                    ->where('status', 'Active')
                    ->when($company_id, fn($query) => $query->where('company', $company_id))
                    ->orderBy('full_name', 'asc')
                    ->get();

            $attendanceData = $this->processAttendanceFile($filePath, $formattedDate)
                    ->keyBy('Ac-No'); // Faster lookup by Ac-No

            $attendanceStatus = $users->map(fn($user) => [
                'staff_id' => $user->staff_id,
                'name' => $user->full_name,
                'present_status' => $attendanceData->has($user->staff_id) ? 1 : 0,
                'date' => $attendanceData[$user->staff_id]['sTime'] ?? null,
            ]);

            return response()->json(['data' => $attendanceStatus, 'status' => 'success'], 200);
        } catch (\Exception $e) {
            return response()->json(['status' => 'error', 'message' => $e->getMessage()], 500);
        }
    }

    private function processAttendanceFile($filePath, $formattedDate) {
        $attendanceData = collect();

        if (($handle = fopen($filePath, 'r')) !== false) {
            $headers = fgetcsv($handle);
            if (!$headers)
                return collect(); // Return empty if headers missing

            while (($row = fgetcsv($handle)) !== false) {
                $row = array_combine($headers, $row);

                // Skip rows with missing values
                if (empty($row['Ac-No']) || empty($row['sTime']))
                    continue;

                try {
                    $csvDate = Carbon::createFromFormat('d/m/Y h:i A', $row['sTime'])->format('d/m/Y');
                    if ($csvDate === $formattedDate) {
                        $attendanceData->put($row['Ac-No'], $row);
                    }
                } catch (\Exception $e) {
                    continue;
                }
            }
            fclose($handle);
        }

        return $attendanceData;
    }

    public function getMonthlyAttendanceData(Request $request) {
        try {
            $company_id = $request->input('company_id');
            $department = $request->input('department');
            $status = $request->input('status') ?? 'Active';
            $year = $request->input('year', date('Y'));
            $month = $request->input('month', date('m'));

            $filePath = public_path('csv/dataset.csv'); // Path to the dataset.csv file

            if (!file_exists($filePath)) {
                return response()->json(['status' => 'error', 'message' => 'Attendance file not found'], 404);
            }

            // Fetch users based on filters
            $query = User::query()->orderBy('full_name', 'asc');
            if (!empty($status))
                $query->where('status', $status);
            if (!empty($company_id))
                $query->where('company', $company_id);
            if (!empty($department))
                $query->where('department', $department);
            $users = $query->get();

            // Generate all dates for the selected month
            $daysInMonth = Carbon::createFromDate($year, $month, 1)->daysInMonth;
            $dates = collect(range(1, $daysInMonth))->map(fn($day) => Carbon::createFromDate($year, $month, $day)->format('d/m/Y'));

            // Process attendance file
            $attendanceData = $this->processMonthlyAttendanceFile($filePath, $dates);

            // Map attendance data to users
            $monthlyData = $users->map(function ($user) use ($attendanceData, $dates) {
                $userAttendance = [
                    'name' => $user->full_name . ' | ' . $user->staff_id,
                    'total' => 0,
                    'days' => []
                ];

                foreach ($dates as $date) {
                    $present = $attendanceData->contains(fn($row) => trim($row['Ac-No']) === $user->staff_id && $row['formattedDate'] === $date);
                    $userAttendance['days'][$date] = $present ? 1 : 0;
                    $userAttendance['total'] += $present ? 1 : 0;
                }

                return $userAttendance;
            });

            return response()->json(['data' => $monthlyData, 'status' => 'success'], 200);
        } catch (\Exception $e) {
            return response()->json(['status' => 'error', 'message' => $e->getMessage()], 500);
        }
    }

    private function processMonthlyAttendanceFile($filePath, $dates) {
        $allRows = collect();

        if (($handle = fopen($filePath, 'r')) !== false) {
            $headers = fgetcsv($handle);
            if (!$headers || !in_array('Ac-No', $headers) || !in_array('sTime', $headers)) {
                fclose($handle);
                return collect(); // Return empty if headers are missing
            }

            while (($row = fgetcsv($handle)) !== false) {
                $row = array_combine($headers, $row);

                if (empty($row['Ac-No']) || empty($row['sTime']))
                    continue; // Skip invalid rows

                try {
                    $csvDate = Carbon::createFromFormat('d/m/Y h:i A', $row['sTime'])->format('d/m/Y');
                    if ($dates->contains($csvDate)) {
                        $row['formattedDate'] = $csvDate;
                        $allRows->push($row);
                    }
                } catch (\Exception $e) {
                    continue; // Skip rows with invalid dates
                }
            }
            fclose($handle);
        }

        return $allRows;
    }

    public function getYearlyAttendanceData(Request $request) {
        try {
            $company_id = $request->input('companyId');
            $department = $request->input('department');
            $status = $request->input('status') ?? 'Active';
            $year = $request->input('year', date('Y'));

            $filePath = public_path('csv/dataset.csv');
            if (!file_exists($filePath)) {
                return response()->json(['status' => 'error', 'message' => 'Attendance file not found'], 404);
            }

            // Fetch all users
            $query = User::query()->orderBy('full_name', 'asc');
            if (!empty($status))
                $query->where('status', $status);
            if (!empty($company_id))
                $query->where('company', $company_id);
            if (!empty($department))
                $query->where('department', $department);
            $users = $query->get();

            // Get attendance data from the CSV file
            $attendanceData = $this->processYearlyAttendanceFile($filePath, $year);

            $holidays = Holiday::select('date')->whereYear('date', $year)
                    ->orderBy('date', 'asc')
                    ->get();

            $yearlyData = $users->map(function ($user) use ($attendanceData, $year) {
                $yearlyPresent = 0;
                $yearlyAbsent = 0;
                $monthlyAttendance = [];

                foreach (range(1, 12) as $month) {
                    $daysInMonth = Carbon::createFromDate($year, $month, 1)->daysInMonth;

                    $monthlyDates = collect(range(1, $daysInMonth))->map(fn($day) => Carbon::createFromDate($year, $month, $day));
                    $formattedDates = $monthlyDates->map(fn($date) => $date->format('d/m/Y'));

                    // Find all Fridays in this month
                    $fridays = $monthlyDates->filter(fn($date) => $date->dayOfWeek === Carbon::FRIDAY)->map(fn($date) => $date->format('d/m/Y'));

                    // Get user's attendance for this month (only actual fingerprint days)
                    $userAttendance = $attendanceData->filter(fn($row) => trim($row['Ac-No']) === $user->staff_id && $formattedDates->contains($row['formattedDate']));

                    // If the user has no attendance at all for this month, skip this month
                    if ($userAttendance->isEmpty()) {
                        continue;
                    }

                    // **Present count is only fingerprint attendance days**
                    $presentCount = $userAttendance->count();

                    // **Absent formula: (Total Days - Present) - Fridays**
                    $absentCount = ($daysInMonth - $presentCount) - $fridays->count();

                    // Ensure absent count is not negative
                    $absentCount = max($absentCount, 0);

                    $monthName = Carbon::createFromDate($year, $month, 1)->format('F');

                    // Store attendance data for the month
                    $monthlyAttendance[$monthName] = [
                        'Present' => $presentCount,
                        'Absent' => $absentCount,
                    ];

                    $yearlyPresent += $presentCount;
                    $yearlyAbsent += $absentCount;
                }

                // Adjusting the total to match frontend expectations
                $total = [
                    'Present' => $yearlyPresent,
                    'Absent' => $yearlyAbsent,
                ];

                return [
                    'name' => $user->full_name . ' | ' . $user->staff_id,
                    'monthly' => $monthlyAttendance,
                    'total' => $total,
                ];
            });
            return response()->json(['data' => $yearlyData, 'holidays' => $holidays, 'status' => 'success'], 200);
        } catch (\Exception $e) {
            return response()->json(['status' => 'error', 'message' => $e->getMessage()], 500);
        }
    }

    private function processYearlyAttendanceFile($filePath, $year) {
        $allRows = collect();

        if (($handle = fopen($filePath, 'r')) !== false) {
            $headers = fgetcsv($handle);
            if (!$headers || !in_array('Ac-No', $headers) || !in_array('sTime', $headers)) {
                fclose($handle);
                return collect();
            }

            while (($row = fgetcsv($handle)) !== false) {
                $row = array_combine($headers, $row);

                if (empty($row['Ac-No']) || empty($row['sTime']))
                    continue;

                try {
                    // Parse the time from sTime and convert it into a date format d/m/Y
                    $csvDate = Carbon::createFromFormat('d/m/Y h:i A', $row['sTime'])->format('d/m/Y');
                    if (Carbon::createFromFormat('d/m/Y', $csvDate)->year == $year) {
                        $row['formattedDate'] = $csvDate; // Store formatted date
                        $allRows->push($row); // Push the row to collection
                    }
                } catch (\Exception $e) {
                    // Skip any rows with invalid date formats
                    continue;
                }
            }
            fclose($handle);
        }

        return $allRows;
    }

    private function getAllFridaysOfYear($year) {
        $fridays = [];
        $startDate = Carbon::createFromDate($year, 1, 1);
        $endDate = Carbon::createFromDate($year, 12, 31);

        while ($startDate->lte($endDate)) {
            if ($startDate->dayOfWeek === Carbon::FRIDAY) {
                $fridays[] = $startDate->format('d/m/Y');
            }
            $startDate->addDay();
        }

        return $fridays;
    }

    // Need to make Payroll data for salary checking purpose 
    public function get_monthly_payroll_data(Request $request) {
        try {
            $company_id = $request->input('company_id');
            $department = $request->input('department');
            $status = $request->input('status') ?? 'Active';
            $year = $request->input('year', date('Y'));
            $month = $request->input('month', date('m'));
            $filePath = public_path('csv/dataset.csv');

            if (!file_exists($filePath)) {
                return response()->json(['status' => 'error', 'message' => 'Attendance file not found'], 404);
            }
            // Fetch users based on filters
            $query = User::query()->orderBy('full_name', 'asc');
            if (!empty($status))
                $query->where('status', $status);
            if (!empty($company_id))
                $query->where('company', $company_id);
            if (!empty($department))
                $query->where('department', $department);
            $users = $query->get();

            // Generate all dates for the selected month
            $daysInMonth = Carbon::createFromDate($year, $month, 1)->daysInMonth;
            $dates = collect(range(1, $daysInMonth))->map(fn($day) => Carbon::createFromDate($year, $month, $day)->format('d/m/Y'));

            // Process attendance file
            $attendanceData = $this->processMonthlyAttendanceFile($filePath, $dates);

            // Fetch holidays for the selected month
            $holidays = Holiday::whereYear('date', $year)
                    ->whereMonth('date', $month)
                    ->pluck('date')
                    ->map(fn($date) => Carbon::parse($date)->format('d/m/Y'));

            // Get all Fridays of the selected month
            $fridays = collect(range(1, $daysInMonth))->map(fn($day) =>
                            Carbon::createFromDate($year, $month, $day)
                    )->filter(fn($date) => $date->isFriday())->map(fn($date) => $date->format('d/m/Y'));

            // Fetch all leave data in advance
            $leaves = Leave::whereYear('start_date', $year)
                    ->whereMonth('start_date', $month)
                    ->where('status', 'Approved') // Only count approved leaves
                    ->get()
                    ->groupBy('user_id');

            // Map attendance data to users
            $monthlyData = $users->map(function ($user) use ($attendanceData, $dates, $holidays, $fridays, $leaves) {
                $userLeaves = $leaves[$user->id] ?? collect();
                $present = 0;
                $absent = 0;
                $holiday = $holidays->count() + $fridays->count();
                $validLeaveDays = collect();

                foreach ($dates as $date) {
                    $isPresent = $attendanceData->contains(fn($row) => trim($row['Ac-No']) === $user->staff_id && $row['formattedDate'] === $date);
                    $isHoliday = $holidays->contains($date);
                    $isFriday = $fridays->contains($date);
                    if ($isPresent) {
                        $present++;
                    } elseif (!$isHoliday && !$isFriday) {
                        $absent++;
                    }
                }

                // Process each leave and filter out overlaps with present days or holidays
                foreach ($userLeaves as $leave) {
                    $start = Carbon::parse($leave->start_date);
                    $end = Carbon::parse($leave->end_date);

                    while ($start <= $end) {
                        $leaveDate = $start->format('d/m/Y');

                        // Count only if it's not a present day or a holiday
                        if (!$attendanceData->contains(fn($row) => trim($row['Ac-No']) === $user->staff_id && $row['formattedDate'] === $leaveDate) &&
                                !$holidays->contains($leaveDate) &&
                                !$fridays->contains($leaveDate)) {
                            $validLeaveDays->push($leaveDate);
                        }

                        $start->addDay();
                    }
                }
                return [
                    'name' => $user->full_name,
                    'present' => $present,
                    'holiday' => $present > 0 ? $holiday : 0,
                    'leave' => $present > 0 ? $validLeaveDays->count() : 0,
                    'payroll_dates' => $present > 0 ? $present + $holiday + $validLeaveDays->count() : 0,
                    'absent' => $present > 0 ? $absent - $validLeaveDays->count() : 0,
                    'daysInMonth' => $dates->count(),
                ];
            });

            return response()->json(['data' => $monthlyData, 'status' => 'success'], 200);
        } catch (\Exception $e) {
            return response()->json(['status' => 'error', 'message' => $e->getMessage()], 500);
        }
    }
}
