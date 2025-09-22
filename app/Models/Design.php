<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Design extends Model {

    protected $table = 'designs';
    protected $fillable = [
        'user_id',
        'design_number',
        'title',
        'design_type',
        'buyers',
        'description',
        'status',
    ];

    protected static function boot() {
        parent::boot();

        static::creating(function ($model) {
            $lastDesign = static::latest('design_number')->first();

            if ($lastDesign) {
                $lastDesignNumber = $lastDesign->design_number;
                $model->design_number = $model->incrementDesignNumber($lastDesignNumber);
            } else {
                $model->design_number = 'MFD-1';
            }
        });
    }

    private function incrementDesignNumber($lastDesignNumber) {
        // Extract the numeric part
        $numericPart = (int) substr($lastDesignNumber, strlen('MFD-'));

        // Increment the numeric part
        $newNumericPart = $numericPart + 1;

        // Set the new SOR number
        return 'MFD-' . $newNumericPart;
    }

}
