<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Issue extends Model {

    protected $table = 'issues';
    protected $fillable = [
        'user_id',
        'store_id',
        'issue_type',
        'issue_to',
        'line',
        'reference',
        'issuing_company',
        'delivery_challan',
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
            $lastIssue = static::latest('delivery_challan')->first();

            if ($lastIssue) {
                $lastIssueNumber = $lastIssue->delivery_challan;
                $model->delivery_challan = $model->incrementIssueNumber($lastIssueNumber);
            } else {
                // If no previous record, start with DCN-1
                $model->delivery_challan = 'DCN-1';
            }
        });
    }

    private function incrementIssueNumber($lastIssueNumber) {
        // Extract the numeric part
        $numericPart = (int) substr($lastIssueNumber, strlen('DCN-'));

        // Increment the numeric part
        $newNumericPart = $numericPart + 1;

        // Set the new DCN number
        return 'DCN-' . $newNumericPart;
    }

}
