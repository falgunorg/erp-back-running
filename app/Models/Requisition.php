<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Requisition extends Model {

    protected $table = 'requisitions';

    protected static function boot() {
        parent::boot();
        static::creating(function ($model) {
            do {
                $randomNum = mt_rand(10000, 99999);
                $model->requisition_number = $randomNum;
            } while (static::where('requisition_number', $model->requisition_number)->exists()); // Check if the generated number already exists, generate a new one if it does
        });
    }

}
