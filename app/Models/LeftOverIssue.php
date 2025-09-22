<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class LeftOverIssue extends Model {

    protected $table = 'left_over_issues';
    protected $fillable = [
        'user_id',
        'left_over_id',
        'issue_type',
        'reference',
        'issue_to_company_id',
        'delivery_challan',
        'buyer_id',
        'style_id',
        'remarks',
        'title',
        'carton',
        'qty',
        'photo',
        'challan_copy',
        'item_type',
        'season',
    ];

    protected static function boot() {
        parent::boot();
        static::creating(function ($model) {
            $lastIssue = static::latest('delivery_challan')->first();

            if ($lastIssue) {
                $lastIssueNumber = $lastIssue->delivery_challan;
                $model->delivery_challan = $model->incrementIssueNumber($lastIssueNumber);
            } else {
                // If no previous record, start with LDCN-1
                $model->delivery_challan = 'LDCN-1';
            }
        });
    }

    private function incrementIssueNumber($lastIssueNumber) {
        // Extract the numeric part
        $numericPart = (int) substr($lastIssueNumber, strlen('LDCN-'));

        // Increment the numeric part
        $newNumericPart = $numericPart + 1;

        // Set the new LDCN number
        return 'LDCN-' . $newNumericPart;
    }

}
