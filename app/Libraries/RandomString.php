<?php

namespace App\Libraries;

class RandomString {

    public static function gen_code($length = 64) {
        $code_chars = array('a', 'b', 'v', 'g', 'd', 'e', 'zh', 'z', 'i', 'j', 'k', 'l', 'm', 'n', 'o', 'p', 'r', 's', 't', 'u', 'f', 'h', 'c', 'A', 'B', 'C', 'D', 'E', 'F', 'G', 'H', 'I', 'J', 'K', 'L', 'M', 'N', 'O', 'P', 'Q', 'R', 'S', 'T', 'U', 'V', 'W', 'X', 'Y', 'Z', '0', '1', '2', '3', '4', '5', '6', '7', '8', '9');
        $code_str = "";
        for ($f = 0; $f < $length; $f++) {
            $r = round(rand(0, count($code_chars) - 1));
            $code_str .= $code_chars[$r];
        }
        return $code_str;
    }

}
