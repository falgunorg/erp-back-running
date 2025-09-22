<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;

class Po extends Model {

    use HasFactory;

    protected $table = 'pos';
    protected $fillable = [
        'user_id', 'po_number', 'wo_id', 'issued_date', 'delivery_date',
        'purchase_contract_id', 'technical_package_id', 'destination', 'ship_mode',
        'shipping_terms', 'packing_method', 'payment_terms', 'total_qty', 'total_value',
    ];

    public function items() {
        return $this->hasMany(PoItem::class);
    }

    public function files() {
        return $this->hasMany(PoFile::class);
    }

    public function techpack() {
        return $this->belongsTo(TechnicalPackage::class, 'technical_package_id', 'id');
    }

    public function user() {
        return $this->belongsTo(User::class);
    }

    public function contract() {
        return $this->belongsTo(PurchaseContract::class, 'purchase_contract_id', 'id');
    }

    public function payment_term() {
        return $this->belongsTo(Term::class, 'payment_terms', 'id');
    }

    public function shipping_term() {
        return $this->belongsTo(Term::class, 'shipping_terms', 'id');
    }

    public function wo() {
        return $this->belongsTo(WorkOrder::class, 'wo_id');
    }
}
