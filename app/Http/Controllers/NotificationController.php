<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Notification;

// Import the Mailable class
class NotificationController extends Controller {

    public function index(Request $request) {
        try {
            $statusCode = 422;
            $return = [];
            $is_read = $request->input('is_read');

            $query = Notification::where('receiver', $request->user->id)->orderBy('created_at', 'desc');
            if ($is_read == 0) {
                $query->where('is_read', 0);
            }

            $notifications = $query->get();
            if ($notifications) {
                $return['notifications'] = $notifications;
                $statusCode = 200;
            } else {
                $return['notifications'] = [];
                $statusCode = 200;
            }
            return $this->response($return, $statusCode);
        } catch (\Throwable $ex) {
            return $this->error(['status' => 'error', 'main_error_message' => $ex->getMessage()]);
        }
    }

    public function read(Request $request) {
        try {
            $statusCode = 422;
            $return = [];
            $id = $request->input('id');

            $notification = Notification::where('id', $id)->first();
            $notification->is_read = 1;
            $notification->save();

            $return['notification'] = $notification;
            $statusCode = 200;

            return $this->response($return, $statusCode);
        } catch (\Throwable $ex) {
            return $this->error(['status' => 'error', 'main_error_message' => $ex->getMessage()]);
        }
    }

}
