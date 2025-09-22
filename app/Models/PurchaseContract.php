<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PurchaseContract extends Model {

    protected $table = 'purchase_contracts';
    protected $fillable = [
        'buyer_id',
        'company_id',
        'season',
        'year',
        'currency',
        'title',
        'pcc_avail',
        'issued_date',
        'shipment_date',
        'expiry_date',
    ];

}
