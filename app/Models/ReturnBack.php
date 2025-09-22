<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ReturnBack extends Model {

    protected $table = 'returns';
    protected $fillable = [
        'user_id',
        'return_to',
        'store_id',
        'issue_id',
        'qty',
        'company_id',
        'received_by',
        'remarks',
    ];

}
