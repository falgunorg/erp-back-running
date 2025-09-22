<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SampleStore extends Model {

    protected $table = 'sample_store';

    protected static function boot() {
        parent::boot();

        static::creating(function ($model) {
            $lastSor = static::latest('store_number')->first();

            if ($lastSor) {
                $lastSorNumber = $lastSor->store_number;
                $model->store_number = $model->incrementSorNumber($lastSorNumber);
            } else {
                // If no previous record, start with STR-1
                $model->store_number = 'STR-1';
            }
        });
    }

    private function incrementSorNumber($lastSorNumber) {
        // Extract the numeric part
        $numericPart = (int) substr($lastSorNumber, strlen('STR-'));

        // Increment the numeric part
        $newNumericPart = $numericPart + 1;

        // Set the new STR number
        return 'STR-' . $newNumericPart;
    }

    public function sample_balance() {
        return $this->hasOne(SampleBalance::class);
    }

}
