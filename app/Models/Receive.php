<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Receive extends Model {

    protected $table = 'receives';
    protected $fillable = [
        'store_id',
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
        'challan_copy',
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
            $lastSor = static::latest('mrr_number')->first();

            if ($lastSor) {
                $lastSorNumber = $lastSor->mrr_number;
                $model->mrr_number = $model->incrementSorNumber($lastSorNumber);
            } else {
                // If no previous record, start with MRR-1
                $model->mrr_number = 'MRR-1';
            }
        });
    }

    private function incrementSorNumber($lastSorNumber) {
        // Extract the numeric part
        $numericPart = (int) substr($lastSorNumber, strlen('MRR-'));

        // Increment the numeric part
        $newNumericPart = $numericPart + 1;

        // Set the new MRR number
        return 'MRR-' . $newNumericPart;
    }

}
