<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str; // Add this line to import the Str class

class Parcel extends Model {

    protected $table = 'parcels';
    protected $guarded = [];

    protected static function boot() {
        parent::boot();

        static::creating(function ($model) {
            do {
                $randomPart = Str::random(8); // Generate a random 8-character string
                $model->tracking_number = $randomPart;
            } while (static::where('tracking_number', $randomPart)->exists()); // Check if the generated number already exists, generate a new one if it does
        });
    }

}
