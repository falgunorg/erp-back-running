<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Store extends Model {

    protected $table = 'stores';
    protected $fillable = [
        'booking_item_id',
        'booking_user_id',
        'supplier_id',
        'buyer_id',
        'company_id',
        'booking_id',
        'budget_id',
        'budget_item_id',
        'techpack_id',
        'challan_no',
        'gate_pass',
        'qty',
        'description',
        'remarks',
        'color',
        'size',
        'shade',
        'tex',
        'unit',
        'photo',
    ];

    protected static function boot() {
        parent::boot();

        static::creating(function ($model) {
            $lastSor = static::latest('store_number')->first();

            if ($lastSor) {
                $lastSorNumber = $lastSor->store_number;
                $model->store_number = $model->incrementSorNumber($lastSorNumber);
            } else {
                // If no previous record, start with MSTR-1
                $model->store_number = 'MSTR-1';
            }
        });
    }

    private function incrementSorNumber($lastSorNumber) {
        // Extract the numeric part
        $numericPart = (int) substr($lastSorNumber, strlen('MSTR-'));

        // Increment the numeric part
        $newNumericPart = $numericPart + 1;

        // Set the new MSTR number
        return 'MSTR-' . $newNumericPart;
    }

}
