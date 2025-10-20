<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Requisition extends Model {

    protected $table = 'requisitions';
   protected $fillable = [
        'user_id',
        'company_id',
        'department',
        'recommended_user',
        'label',
        'billing_company_id',
        'total',
        'placed_by'
    ];

    protected static function boot() {
        parent::boot();
        static::creating(function ($model) {
            do {
                $randomNum = mt_rand(10000, 99999);
                $model->requisition_number = $randomNum;
            } while (static::where('requisition_number', $model->requisition_number)->exists()); // Check if the generated number already exists, generate a new one if it does
        });
    }

    public function items() {
        return $this->hasMany(RequisitionItem::class, 'requisition_id');
    }

    public function user() {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function company() {
        return $this->belongsTo(Company::class, 'company_id');
    }

    public function billing_company() {
        return $this->belongsTo(Company::class, 'billing_company_id');
    }

    public function department() {
        return $this->belongsTo(Department::class, 'department');
    }

    //users 
    public function placed_by_user() {
        return $this->belongsTo(User::class, 'placed_by');
    }

    public function recommended_by_user() {
        return $this->belongsTo(User::class, 'recommended_by');
    }

    public function valuated_by_user() {
        return $this->belongsTo(User::class, 'valuated_by');
    }

    public function checked_by_user() {
        return $this->belongsTo(User::class, 'checked_by');
    }

    public function rejected_by_user() {
        return $this->belongsTo(User::class, 'rejected_by');
    }

    public function approved_by_user() {
        return $this->belongsTo(User::class, 'approved_by');
    }

    public function finalized_by_user() {
        return $this->belongsTo(User::class, 'finalized_by');
    }
}
