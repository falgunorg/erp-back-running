<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\AccessToken;
use Illuminate\Http\Request;
use GuzzleHttp\TransferStats;
use GuzzleHttp\Client as GuzzleClient;
use GuzzleHttp\Exception\GuzzleException;
use Illuminate\Support\Facades\Validator;
//use Illuminate\Http\File;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Response;
use Illuminate\Support\Facades\Storage;
use App\Models\MailSign;

class UserController extends Controller {

//    public function __construct(Request $request) {
//        parent::__construct($request);
//    }

    public function login(Request $request) {
        try {
            $statusCode = 422;
            $email = $request->input('email');
            $password = $request->input('password');
            $return = [];

            if (strlen($email) == 0) {
                $return['errors']['email'] = 'Please enter your email';
            }

            if (strlen($password) == 0) {
                $return['errors']['password'] = 'Please enter the password';
            }

            $password = \App\Libraries\Tokenizer::password($password);

            if (!isset($return['errors'])) {

                $user = User::where('email', $email)->where('password', $password)->first();

                if ($user) {
                    $user->last_login_at = date('Y-m-d H:i:s');
                    $user->save();
                    $refreshToken = AccessToken::saveToken($request, $user->id);
                    $accessToken = \App\Libraries\Tokenizer::encrypt([
                                'id' => $user->id,
                                'email' => $user->email,
                                'full_name' => $user->full_name,
                    ]);

                    $role_permission = \App\Models\Role::where('id', $user->role_permission)->first();
                    $department = \App\Models\Department::where('id', $user->department)->first();
                    $designation = \App\Models\Designation::where('id', $user->designation)->first();

                    if (@$user->photo != '') {
                        $file_path = url('') . '/profile_pictures/' . $user->photo;
                    } else {
                        $file_path = url('') . '/profile_pictures/user-avatar.png';
                    }

                    $userObject = [
                        'accessToken' => $accessToken,
                        'refreshToken' => $refreshToken->token,
                        'userId' => $user->id,
                        'full_name' => $user->full_name,
                        'email' => $user->email,
                        'profile_picture' => $file_path,
                        'role' => $role_permission->title,
                        'company_id' => $user->company,
                        'designation_title' => $designation->title,
                        'department_title' => $department->title,
                        'department' => $department->id,
                        'last_login_at' => $user->last_login_at,
                        'lifetime' => 300,
                    ];
                    $statusCode = 200;
                    $return['status'] = 'success';
                    $return['user'] = $userObject;
                } else {
                    $return['errorMsg'] = 'Email or Password not correct';
                    $return['status'] = 'error';
                    $statusCode = 422;
                }
            } else {
                $return['status'] = 'error';
                $statusCode = 422;
            }

            return $this->response($return, $statusCode);
        } catch (\Throwable $ex) {
            return $this->error(['status' => "error", 'errorMsg' => $ex->getMessage()]);
        }
    }

    public function refreshToken(Request $request) {
        $statusCode = 422;
        try {
            $token = AccessToken::where('token', $request->token)->where('expires_at', '>=', date('Y-m-d H:i:s'))->first();
            $user = null;
            if ($token) {
                $user = User::find($token->user_id);
            }
            if ($user) {
                $role_permission = \App\Models\Role::where('id', $user->role_permission)->first();
                $department = \App\Models\Department::where('id', $user->department)->first();
                $designation = \App\Models\Designation::where('id', $user->designation)->first();
                if (@$user->photo != '') {
                    $file_path = url('') . '/profile_pictures/' . $user->photo;
                } else {
                    $file_path = url('') . '/profile_pictures/user-avatar.png';
                }
                $accessToken = \App\Libraries\Tokenizer::encrypt([
                            'id' => $user->id,
                            'email' => $user->email,
                            'full_name' => $user->full_name,
                ]);

                $userObject = [
                    'accessToken' => $accessToken,
                    'refreshToken' => $request->token,
                    'userId' => $user->id,
                    'full_name' => $user->full_name,
                    'email' => $user->email,
                    'profile_picture' => $file_path,
                    'role' => $role_permission->title,
                    'company_id' => $user->company,
                    'designation_title' => $designation->title,
                    'department_title' => $department->title,
                    'department' => $department->id,
                    'last_login_at' => $user->last_login_at,
                    'lifetime' => 300,
                ];
                $token->last_used_at = date('Y-m-d H:i:s');
                $token->save();
                $statusCode = 200;
                return $this->response($userObject, $statusCode);
            } else {
                throw "User not found";
            }
        } catch (\Throwable $ex) {
            return $this->error(['status' => "error", 'message' => $ex->getMessage() . ' Line No:' . $ex->getLine() . ' File Name:' . $ex->getFile()]);
        }
    }

    public function profile(Request $request) {
        try {
            $statusCode = 422;
            $return = [];
            $id = $request->user->id;
            $employee = User::find($id);
            $employee->profile_picture = url('') . '/profile_pictures/' . $employee->photo;
            $employee->signature = url('') . '/signs/' . $employee->sign;

            $statusCode = 200;
            $return['data'] = $employee;
            $return['status'] = 'success';
            return $this->response($return, $statusCode);
        } catch (\Throwable $ex) {
            return $this->error(['status' => "error", 'main_error_message' => $ex->getMessage()]);
        }
    }

    public function update_profile(Request $request) {
        try {
            $statusCode = 422;
            $return = [];
            $user_id = $request->user->id;
            $new_password = $request->input('new_password');
            $confirm_password = $request->input('confirm_password');

            $user = User::where('id', $user_id)->first();

            if (strlen($new_password) == 0) {
                $return['errors']['password'] = 'Please insert New Password';
            }
            if (strlen($confirm_password) == 0) {
                $return['errors']['password'] = 'Please insert Confirm Password';
            }
            if ($new_password !== $confirm_password) {
                $return['errors']['password'] = 'Password is not match';
            }

            if (!isset($return['errors'])) {
                $user->password = \App\Libraries\Tokenizer::password($new_password);
                $user->save();

                $statusCode = 200;
                $return['status'] = 'success';
            }

            return $this->response($return, $statusCode);
        } catch (\Throwable $ex) {
            return $this->error(['status' => "error", 'main_error_message' => $ex->getMessage()]);
        }
    }

    public function update_profile_picture(Request $request) {
        try {
            $statusCode = 422;
            $return = [];
            $user_id = $request->user->id;
            $user = User::where('id', $user_id)->first();
            if (isset($_FILES['photo']['name'])) {

                $public_path = public_path();
                $path = $public_path . '/' . "profile_pictures";
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
                    $user->photo = $file_name;
                }
            }
            $user->save();

            $statusCode = 200;
            $return['status'] = 'success';

            return $this->response($return, $statusCode);
        } catch (\Throwable $ex) {
            return $this->error(['status' => "error", 'main_error_message' => $ex->getMessage()]);
        }
    }

    public function update_signature(Request $request) {
        try {
            $statusCode = 422;
            $return = [];
            $user_id = $request->user->id;
            $user = User::where('id', $user_id)->first();
            if (isset($_FILES['sign']['name'])) {

                $public_path = public_path();
                $path = $public_path . '/' . "signs";
                $pathinfo = pathinfo($_FILES['sign']['name']);

                $basename = strtolower(str_replace(' ', '_', $pathinfo['filename']));
                $extension = strtolower($pathinfo['extension']);

                $file_name = $basename . '.' . $extension;

                $finalpath = $path . '/' . $file_name;
                if (file_exists($finalpath)) {
                    $file_name = $basename . time() . '.' . $extension;
                    $finalpath = $path . '/' . $file_name;
                }
                if (move_uploaded_file($_FILES['sign']['tmp_name'], $finalpath)) {
                    $user->sign = $file_name;
                }
            }
            $user->save();
            $statusCode = 200;
            $return['status'] = 'success';
            return $this->response($return, $statusCode);
        } catch (\Throwable $ex) {
            return $this->error(['status' => "error", 'main_error_message' => $ex->getMessage()]);
        }
    }

    public function mailSign(Request $request) {
        $return = [];
        $user_id = $request->input('user_id');
        $sign = MailSign::where('user_id', $user_id)->first();

        if ($sign) {
            $description = $sign->description;
        } else {
            $description = "";
        }

        $statusCode = 200;
        $return['data'] = $description;
        $return['status'] = 'success';
        return $this->response($return, $statusCode);
    }

}
