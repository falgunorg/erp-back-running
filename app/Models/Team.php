<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Team extends Model {

    protected $table = 'teams';
    protected $fillable = [
        'user_id',
        'team_number',
        'title',
        'team_lead',
        'employees',
        'department',
        'company_id',
        'description',
    ];

}
