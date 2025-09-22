<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PartRequest extends Model {

    protected $table = 'parts_request';

    protected static function boot() {
        parent::boot();
        static::creating(function ($model) {
            do {
                $randomNum = mt_rand(10000, 99999);
                $model->request_number = $randomNum;
            } while (static::where('request_number', $model->request_number)->exists()); // Check if the generated number already exists, generate a new one if it does
        });
    }

}
