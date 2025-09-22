<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class LeftOver extends Model {

    protected $table = 'left_overs';
    protected $fillable = [
        'user_id',
        'left_over_id',
        'company_id',
        'buyer_id',
        'style_id',
        'season',
        'title',
        'carton',
        'qty',
        'item_type',
        'received_by',
        'reference',
        'remarks',
        'photo',
    ];

}
