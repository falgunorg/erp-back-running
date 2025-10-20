<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class RequisitionItem extends Model {

    protected $table = 'requisition_items';
    protected $fillable = [
        'requisition_id',
        'part_id',
        'stock_in_hand',
        'unit',
        'qty',
        'recommand_qty',
        'audit_qty',
        'final_qty',
        'purchase_qty',
        'finalized_by',
        'rate',
        'final_rate',
        'total',
        'remarks',
        'status'
    ];

    public function requisition() {
        return $this->belongsTo(Requisition::class, 'requisition_id');
    }

    public function part() {
        return $this->belongsTo(Part::class, 'part_id');
    }
}
