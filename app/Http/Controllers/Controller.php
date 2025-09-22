<?php

namespace App\Http\Controllers;

use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Foundation\Bus\DispatchesJobs;
use Illuminate\Foundation\Validation\ValidatesRequests;
use Illuminate\Routing\Controller as BaseController;

class Controller extends BaseController {

    use AuthorizesRequests,
        DispatchesJobs,
        ValidatesRequests;

    protected $request;

    public function success($data, $code = 200) {
        return response()->json($data, $code);
    }

    public function error($message, $code = 422) {
        return response()->json($message, $code);
    }

    public function response($response, $statusCode = null) {
        if ($statusCode == 200) {
            return $this->success($response, $statusCode);
        } else {
            return $this->error($response, 422);
        }
    }

    protected function gen_code($length = 64) {
        return \App\Libraries\RandomString::gen_code($length);
    }

    protected function errorException($ex, $status = 422) {
        if ($ex instanceof Warning) {
            return $this->error(['message' => $ex->getMessage()], $status);
        }
        if (env('APP_DEBUG')) {
            return $this->error(['message' => $ex->getMessage() . ' on line ' . $ex->getLine() . ' on file ' . $ex->getFile()], $status);
        }
        return $this->error(['message' => 'Something went wrong'], $status);
    }

    protected function errorValidation($errorBag, $status = 422) {
        $newErrors = [];
        foreach ($errorBag->toArray() as $key => $value) {
            if (is_array($value)) {
                $newErrors[$key] = $value[0];
            } else {
                $newErrors[$key] = $value;
            }
        }
        return $this->error(['validationErrors' => $newErrors], $status);
    }
}
