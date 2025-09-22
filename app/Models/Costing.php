<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Costing extends Model {

    protected $table = 'costings';
    protected $fillable = [
        'user_id',
        'costing_ref',
        'po_id',
        'wo_id',
        'technical_package_id',
        'factory_cpm',
        'fob',
        'cm'
    ];

    public function items() {
        return $this->hasMany(CostingItem::class);
    }

    public function user() {
        return $this->belongsTo(User::class);
    }

    public function techpack() {
        return $this->belongsTo(TechnicalPackage::class, 'technical_package_id');
    }
}
