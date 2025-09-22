<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TechnicalPackageMaterial extends Model {

    protected $table = 'technical_package_materials';
    protected $fillable = [
        'technical_package_id', 'item_type_id', 'item_id', 'item_name',
        'item_details', 'color', 'size', 'position', 'unit',
        'consumption', 'wastage', 'total'
    ];

    public function technicalPackage() {
        return $this->belongsTo(TechnicalPackage::class);
    }

    public function item_type() {
        return $this->belongsTo(ItemType::class, 'item_type_id');
    }

    public function item() {
        return $this->belongsTo(Item::class, 'item_id');
    }
}
