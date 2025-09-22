<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Schedule;
use Illuminate\Support\Facades\Validator;

class ScheduleController extends Controller {

    public function index(Request $request) {
        try {
            $statusCode = 422;
            $return = [];
            $schedules = Schedule::where('ownerId', $request->user->id)->orderBy('created_at', 'desc')->get();
            if ($schedules) {

                foreach ($schedules as $schedule) {
                    $user = \App\Models\User::where('id', $schedule->ownerId)->first();
                    $schedule->owner_name = $user->full_name;

                    // Format start and end dates with time
                    $startDateTime = date('Y-m-d g:i A', strtotime($schedule->startDate));
                    $endDateTime = date('Y-m-d g:i A', strtotime($schedule->endDate));

                    // Generate description with date and time
                    $schedule->description = sprintf(
                            "%s created a schedule from %s to %s.",
                            $schedule->owner_name,
                            $startDateTime,
                            $endDateTime
                    );
                }




                $return['data'] = $schedules;
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

            $allDay = $request->input('allDay');
            $endDate = $request->input('endDate');
            $notes = $request->input('notes');
            $rRule = $request->input('rRule');
            $startDate = $request->input('startDate');
            $title = $request->input('title');
            $priority = $request->input('priority');
            $ownerId = $request->user->id;

            $schedule = new Schedule;
            $schedule->allDay = $allDay;
            $schedule->endDate = $endDate;
            $schedule->notes = $notes;
            $schedule->rRule = $rRule;
            $schedule->startDate = $startDate;
            $schedule->title = $title;
            $schedule->priority = $priority ? $priority : "low";
            $schedule->ownerId = $ownerId;
            if ($schedule->save()) {
                $return['data'] = $schedule;
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
            $schedule = Schedule::find($id);

            if ($schedule) {
                $return['data'] = $schedule;
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

            $allDay = $request->input('allDay');
            $endDate = $request->input('endDate');
            $notes = $request->input('notes');
            $rRule = $request->input('rRule');
            $startDate = $request->input('startDate');
            $title = $request->input('title');
            $ownerId = $request->user->id;
            $priority = $request->input('priority');

            $schedule = Schedule::find($id);

            if ($schedule) {
                $schedule->allDay = $allDay;
                $schedule->endDate = $endDate;
                $schedule->notes = $notes;
                $schedule->rRule = $rRule;
                $schedule->startDate = $startDate;
                $schedule->title = $title;
                $schedule->priority = $priority ? $priority : "low";
                $schedule->ownerId = $ownerId;
                if ($schedule->save()) {
                    $return['data'] = $schedule;
                    $statusCode = 200;
                    $return['status'] = 'success';
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
            $id = $request->input('id');
            $schedule_delete = Schedule::where('id', $id)->delete();
            if ($schedule_delete) {
                $return['data'] = "Deleted Success";
                $statusCode = 200;
                $return['status'] = 'success';
            }
            return $this->response($return, $statusCode);
        } catch (\Throwable $ex) {
            return $this->error(['status' => "error", 'main_error_message' => $ex->getMessage()]);
        }
    }
}
