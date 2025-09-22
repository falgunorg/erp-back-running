<?php

namespace App\Libraries;

class PasswordValidator {

    public static function get_error_message() {
        return __("Your password must contain between 6 – 25 characters, incuding at least 1 Uppercase and 1 number / special character.");
    }

    public static function validate($password = null) {
        if (empty($password)) {
            return false;
        }
        //\S*: any set of characters
        //(? = \S{8, }): of at least length 8
        //(? = \S*[a-z]): containing at least one lowercase letter
        //(? = \S*[A-Z]): and at least one uppercase letter
        //(? = \S*[\d\W]): and at least one number/non word character
        $valid = preg_match('~^\S*(?=\S{6,25})(?=\S*[A-Z])(?=\S*[\d\W])\S*$~', $password);

        if (!$valid) {
            return false;
        } else {
            return true;
        }
    }

}
