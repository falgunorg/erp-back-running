<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\User;
use App\Models\Techpack;
use App\Models\TechpackFile;
use App\Models\Sor;
use App\Models\SorFile;
use App\Models\Booking;
use App\Models\BookingFile;
use App\Models\Costing;
use App\Models\CostingFile;
use App\Models\Design;
use App\Models\DesignFile;
use App\Models\Issue;
use App\Models\Proforma;
use App\Models\ProformaFile;
use App\Models\Purchase;
use App\Models\PurchaseFile;

class FileController extends Controller {

    public function index(Request $request) {
        try {
            $statusCode = 422;
            $return = [];
            $user_id = $request->user->id;

            $techpaccks = Techpack::where('user_id', $user_id)->get();

            $return['techpaccks'] = $techpaccks;
            $statusCode = 200;
            $return['status'] = 'success';

            return $this->response($return, $statusCode);
        } catch (\Throwable $ex) {
            return $this->error(['status' => "error", 'main_error_message' => $ex->getMessage()]);
        }
    }

}
