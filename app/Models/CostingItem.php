<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CostingItem extends Model {

    protected $table = 'costing_items';
    protected $fillable = [
        'costing_id', 'item_type_id', 'item_id', 'item_name',
        'item_details', 'color', 'size', 'unit', 'position','supplier_id',
        'consumption', 'wastage', 'total', 'unit_price', 'total_price'
    ];

    public function costing() {
        return $this->belongsTo(Costing::class);
    }

    public function item_type() {
        return $this->belongsTo(ItemType::class, 'item_type_id');
    }
    
    public function supplier() {
          return $this->belongsTo(Supplier::class, 'supplier_id');
    }
    
     public function item() {
          return $this->belongsTo(Item::class, 'item_id');
    }
    
}
