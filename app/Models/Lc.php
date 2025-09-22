<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Lc extends Model {

    protected $table = 'lcs';

    protected static function boot() {
        parent::boot();
        static::creating(function ($model) {
            $lastBooking = static::latest('serial_number')->first();
            if ($lastBooking) {
                $lastBookingNumber = $lastBooking->serial_number;
                $model->serial_number = $model->incrementBookingNumber($lastBookingNumber);
            } else {
                // If no previous record, start with LC-1
                $model->serial_number = 'LC-1';
            }
        });
    }

    private function incrementBookingNumber($lastBookingNumber) {
        // Extract the numeric part
        $numericPart = (int) substr($lastBookingNumber, strlen('LC-'));

        // Increment the numeric part
        $newNumericPart = $numericPart + 1;

        // Set the new LC number
        return 'LC-' . $newNumericPart;
    }

}
