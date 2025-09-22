<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Leave extends Model {

    use HasFactory;

    protected $fillable = [
        'user_id',
        'leave_type',
        'start_date',
        'end_date',
        'total_days',
        'status',
        'reason',
        'recommended_at',
        'recommended_by',
        'approved_at',
        'approved_by',
        'rejected_at',
        'rejected_by',
        'company',
        'department',
        'designation',
    ];

    public function user() {
        return $this->belongsTo(User::class);
    }
}
