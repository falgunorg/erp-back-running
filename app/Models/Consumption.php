<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Consumption extends Model {

    protected $table = 'consumptions';
    protected $fillable = [
        'user_id',
        'consumption_number',
        'techpack_id',
        'description',
        'status',
    ];

    protected static function boot() {
        parent::boot();

        static::creating(function ($model) {
            $lastCSMP = static::latest('consumption_number')->first();

            if ($lastCSMP) {
                $lastCSMPNumber = $lastCSMP->consumption_number;
                $model->consumption_number = $model->incrementCSMPNumber($lastCSMPNumber);
            } else {
                // If no previous record, start with CSMP-1
                $model->consumption_number = 'CSMP-1';
            }
        });
    }

    private function incrementCSMPNumber($lastCSMPNumber) {
        // Extract the numeric part
        $numericPart = (int) substr($lastCSMPNumber, strlen('CSMP-'));

        // Increment the numeric part
        $newNumericPart = $numericPart + 1;

        // Set the new CSMP number
        return 'CSMP-' . $newNumericPart;
    }

}
