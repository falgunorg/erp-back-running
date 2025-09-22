<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Supplier extends Model {

    protected $table = 'suppliers';
    protected $fillable = [
        'company_name',
        'email',
        'attention_person',
        'country',
        'mobile_number',
        'status',
        'address',
        'office_number',
        'state',
        'postal_code',
        'vat_reg_number',
        'product_supply',
        'added_by',
        'type',
    ];

}
