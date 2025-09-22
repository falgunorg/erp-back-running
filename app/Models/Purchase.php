<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Purchase extends Model {

    protected $table = 'purchases';
    protected $fillable = [
        'user_id',
        'contract_id',
        'po_number',
        'style',
        'sizes',
        'colors',
        'order_date',
        'shipment_date',
        'delivery_address',
        'shipping_method',
        'packing_instructions',
        'packing_method',
        'comment',
        'total_qty',
        'total_amount',
    ];

    protected static function boot() {
        parent::boot();

        static::creating(function ($model) {
            $lastProforma = static::latest('sd_po')->first();

            if ($lastProforma) {
                $lastProformaNumber = $lastProforma->sd_po;
                $model->sd_po = $model->incrementProformaNumber($lastProformaNumber);
            } else {
                $model->sd_po = 'PO-1';
            }
        });
    }

    private function incrementProformaNumber($lastProformaNumber) {
        // Extract the numeric part
        $numericPart = (int) substr($lastProformaNumber, strlen('PO-'));

        // Increment the numeric part
        $newNumericPart = $numericPart + 1;

        // Set the new SOR number
        return 'PO-' . $newNumericPart;
    }

}
