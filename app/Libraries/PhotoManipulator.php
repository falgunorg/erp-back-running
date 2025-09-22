<?php

namespace App\Libraries;

class PhotoManipulator {

    public static function resize($w, $h, $source_file, $destination_file = false) {

        if (!file_exists($source_file))
            return FALSE;
        $p = pathinfo($source_file);
        $extension = strtolower($p['extension']);
        switch ($extension) {
            case "jpg":
            case "jpeg":
                $image = imagecreatefromjpeg($source_file);
                break;
            case "gif":
                $image = imagecreatefromgif($source_file);
                break;
            case "png":
                $image = imagecreatefrompng($source_file);
                break;
            case "bmp":
                $image = imagecreatefrombmp($source_file);
                break;
            default:
                return FALSE;
        }
        // Get new dimensions
        list ( $width, $height ) = getimagesize($source_file);

        if (!$h) {
            $h = intval($height / ($width / $w));
        } elseif (!$w) {
            $w = intval($width / ($height / $h));
        }
//        
        //if the original images width is less than the expected cropped width
        if ($width < $w) {
            //determine the scale factor that will make the original images width equal to the expected width
            $scaleFactor = $w / $width;
        } else {
            $scaleFactor = 1;
        }
        //after scaling, if the height or the image is less than the expected height
        if ($height * $scaleFactor < $h) {
            $scaleFactor = $h / $height; //it'll be larger than the previos scale factor, cause after calculating with the width based one, the height was still smaller than expected
        }
        $scaledOriginalWidth = $width * $scaleFactor;
        $scaledOriginalHeight = $height * $scaleFactor;
        //calculate how much we can scale our crop dimension width wise
        $cropScaleFactor = $scaledOriginalWidth / $w;
        //if the scaling makes target height bigger than source height
        if ($h * $cropScaleFactor > $scaledOriginalHeight) {
            //calculate the crop scaling height wise
            //it'll be less than the width wise scale. Cause using the width wise scale made the height bigger than expected
            $cropScaleFactor = $scaledOriginalHeight / $h;
        }
        $scaledTargetWidth = $w * $cropScaleFactor;
        $scaledTargetHeight = $h * $cropScaleFactor;
        //now we calculate the actual positions 
        //difference between source and target width divided by 2 will give us the source start x position for a centered base
        //then divide with ScaleFactor to restore original dimentions
        $src_x = (($scaledOriginalWidth - $scaledTargetWidth) / 2) / $scaleFactor;
        $src_y = (($scaledOriginalHeight - $scaledTargetHeight) / 2) / $scaleFactor;
        //we divide them with the scalefactor too, cause they were calculated based on the scaled dimentions of the original image
        $src_width = $scaledTargetWidth / $scaleFactor;
        $src_height = $scaledTargetHeight / $scaleFactor;
        // Resample
        $image_p = imagecreatetruecolor($w, $h);
        //imagecrop($image, $rect)
        if ($extension == 'png') {
            imagealphablending($image_p, false);
            imagesavealpha($image_p, true);
        }
        imagecopyresampled($image_p, $image, 0, 0, $src_x, $src_y, $w, $h, $src_width, $src_height);
        // Content type
        // Output
        if ($destination_file) {
            $dst_extension = strtolower(pathinfo($destination_file, PATHINFO_EXTENSION));
            switch ($dst_extension) {
                case "jpg":
                case "jpeg":
                    imagejpeg($image_p, $destination_file, 100);
                    break;
                case "gif":
                    imagegif($image_p, $destination_file);
                    break;
                case "png":
                    imagepng($image_p, $destination_file, 9);
                    break;
                case "bmp":
                    imagewbmp($image_p, $destination_file);
                    break;
                default:
                    return FALSE;
            }
            chmod($destination_file, 0777);
        } else {
            //output to browser
            switch ($extension) {
                case "jpg":
                case "jpeg":
                    header('Content-type: image/jpeg');
                    imagejpeg($image_p, null, 100);
                    break;
                case "gif":
                    header('Content-type: image/gif');
                    imagegif($image_p, null);
                    break;
                case "png":
                    header('Content-type: image/png');
                    imagepng($image_p, null, 9);
                    break;
                case "bmp":
                    header('Content-type: image/bmp');
                    imagewbmp($image_p, null);
                    break;
                default:
                    return FALSE;
            }
            exit();
        }
        imagedestroy($image_p);
        imagedestroy($image);
        return TRUE;
    }

    public static function serve($source_file) {
        if (!file_exists($source_file))
            return FALSE;
        $p = pathinfo($source_file);
        $extension = strtolower($p['extension']);
        switch ($extension) {
            case "jpg":
            case "jpeg":
                header('Content-type: image/jpeg');
                break;
            case "gif":
                header('Content-type: image/gif');
                break;
            case "png":
                header('Content-type: image/png');
                break;
            case "bmp":
                header('Content-type: image/bmp');
                break;
            default:
                return FALSE;
        }

        echo file_get_contents($source_file);
        return true;
    }

}
