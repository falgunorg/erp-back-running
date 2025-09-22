<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Techpack extends Model {

    protected $table = 'techpacks';
    protected $fillable = [
        'title',
        'user_id',
        'buyer_id',
        'photo',
    ];

    protected static function boot() {
        parent::boot();

        static::creating(function ($model) {
            $lastTechpack = static::latest('techpack_number')->first();

            if ($lastTechpack) {
                $lastTechpackNumber = $lastTechpack->techpack_number;
                $model->techpack_number = $model->incrementTechpackNumber($lastTechpackNumber);
            } else {
                $model->techpack_number = 'TP-1';
            }
        });
    }

    private function incrementTechpackNumber($lastTechpackNumber) {
        // Extract the numeric part
        $numericPart = (int) substr($lastTechpackNumber, strlen('TP-'));

        // Increment the numeric part
        $newNumericPart = $numericPart + 1;

        // Set the new SOR number
        return 'TP-' . $newNumericPart;
    }

}
