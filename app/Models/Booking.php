<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Booking extends Model {

    protected $table = 'bookings';
    protected $fillable = [
        'booking_number',
        'user_id',
        'supplier_id',
        'booking_date',
        'company_id',
        'delivery_date',
        'billing_address',
        'delivery_address',
        'booking_from',
        'booking_to',
        'currency',
        'remark',
        'terms',
        'status',
        'total_amount',
    ];

    protected static function boot() {
        parent::boot();
        static::creating(function ($model) {
            $lastBooking = static::latest('booking_number')->first();
            if ($lastBooking) {
                $lastBookingNumber = $lastBooking->booking_number;
                $model->booking_number = $model->incrementBookingNumber($lastBookingNumber);
            } else {
                // If no previous record, start with BGD-1
                $model->booking_number = 'BN-1';
            }
        });
    }

    private function incrementBookingNumber($lastBookingNumber) {
        // Extract the numeric part
        $numericPart = (int) substr($lastBookingNumber, strlen('BN-'));

        // Increment the numeric part
        $newNumericPart = $numericPart + 1;

        // Set the new BGD number
        return 'BN-' . $newNumericPart;
    }

}
