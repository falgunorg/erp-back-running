<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class BudgetItem extends Model {

    protected $table = 'budget_items';
    protected $fillable = [
        'budget_id',
        'costing_id',
        'item_type_id',
        'item_id',
        'item_name',
        'item_details',
        'color',
        'size',
        'unit',
        'size_breakdown',
        'quantity',
        'position',
        'supplier_id',
        'consumption',
        'wastage',
        'total',
        'total_booking',
        'unit_price',
        'actual_unit_price',
        'total_price',
        'actual_total_price',
    ];

    public function budget() {
        return $this->belongsTo(Budget::class);
    }

    public function supplier() {
        return $this->belongsTo(Supplier::class, 'supplier_id');
    }

    public function item() {
        return $this->belongsTo(Item::class, 'item_id');
    }

    public function itemType() {
        return $this->belongsTo(ItemType::class, 'item_type_id');
    }

    public function costing() {
        return $this->belongsTo(Costing::class);
    }
}
