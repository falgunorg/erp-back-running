<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class LeftOverBalance extends Model {

    protected $table = 'left_over_balance';
    protected $fillable = [
        'user_id',
        'company_id',
        'buyer_id',
        'style_id',
        'season',
        'title',
        'item_type',
        'carton',
        'qty',
        'photo',
    ];

    protected static function boot() {
        parent::boot();

        static::creating(function ($model) {
            $lastLo = static::latest('lo_number')->first();

            if ($lastLo) {
                $lastLoNumber = $lastLo->lo_number;
                $model->lo_number = $model->incrementLoNumber($lastLoNumber);
            } else {
                // If no previous record, start with LO-1
                $model->lo_number = 'LO-1';
            }
        });
    }

    private function incrementLoNumber($lastLoNumber) {
        // Extract the numeric part
        $numericPart = (int) substr($lastLoNumber, strlen('LO-'));
        // Increment the numeric part
        $newNumericPart = $numericPart + 1;
        // Set the new LO number
        return 'LO-' . $newNumericPart;
    }

}
