<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Machine extends Model {

    protected $table = 'machines';
    protected $fillable = [
        'user_id',
        'serial_number',
        'company_id',
    ];

}
