<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class Sor extends Model {

    protected $table = 'sors';

    protected static function boot() {
        parent::boot();

        static::creating(function ($model) {
            $lastSor = static::latest('sor_number')->first();

            if ($lastSor) {
                $lastSorNumber = $lastSor->sor_number;
                $model->sor_number = $model->incrementSorNumber($lastSorNumber);
            } else {
                // If no previous record, start with SOR-1
                $model->sor_number = 'SOR-1';
            }
        });
    }

    private function incrementSorNumber($lastSorNumber) {
        // Extract the numeric part
        $numericPart = (int) substr($lastSorNumber, strlen('SOR-'));

        // Increment the numeric part
        $newNumericPart = $numericPart + 1;

        // Set the new SOR number
        return 'SOR-' . $newNumericPart;
    }

}
