<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Budget extends Model {

    protected $table = 'budgets';
    protected $fillable = [
        'user_id',
        'po_id',
        'wo_id',
        'ref_number',
        'technical_package_id',
        'costing_id',
        'factory_cpm',
        'fob',
        'cm',
        'status',
        'placed_by',
        'placed_at',
        'confirmed_by',
        'confirmed_at',
        'submitted_by',
        'submitted_at',
        'checked_by',
        'checked_at',
        'cost_approved_by',
        'cost_approved_at',
        'finalized_by',
        'finalized_at',
        'approved_by',
        'approved_at',
        'rejected_by',
        'rejected_at',
    ];

    public function items() {
        return $this->hasMany(BudgetItem::class);
    }

    public function user() {
        return $this->belongsTo(User::class);
    }

    public function costing() {
        return $this->belongsTo(Costing::class);
    }

    public function techpack() {
        return $this->belongsTo(TechnicalPackage::class, 'technical_package_id');
    }
}
