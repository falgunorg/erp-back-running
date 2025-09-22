<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ProformaItem extends Model {

    protected $table = 'proforma_items';
    protected $fillable = [
        'proforma_id',
        'item_id',
        'description',
        'booking_id',
        'booking_item_id',
        'budget_id',
        'budget_item_id',
        'hscode',
        'qty',
        'unit',
        'unit_price',
        'total',
    ];

}
