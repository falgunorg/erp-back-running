<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Payroll extends Model {

    use HasFactory;

    protected $fillable = [
        'user_id',
        'staff_id',
        'basic_salary',
        'house_rent',
        'medical_allowance',
        'transport_allowance',
        'food_allowance',
        'gross_salary',
    ];

    public function user() {
        return $this->belongsTo(User::class);
    }
}
