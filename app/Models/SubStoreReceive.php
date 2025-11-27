<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SubStoreReceive extends Model {

    protected $table = 'sub_store_receives';
    protected $fillable = [
        'receive_date',
        'requisition_id',
        'requisition_item_id',
        'user_id',
        'substore_id',
        'company_id',
        'part_id',
        'qty',
        'supplier_id',
        'challan_no',
        'mrr_no',
        'gate_pass',
        'challan_copy',
    ];

    public function user() {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function part() {
        return $this->belongsTo(Part::class, 'part_id');
    }

    public function supplier() {
        return $this->belongsTo(Supplier::class, 'supplier_id');
    }

    public function substore() {
        return $this->belongsTo(SubStore::class, 'substore_id');
    }

    public function requisition() {
        return $this->belongsTo(Requisition::class, 'requisition_id');
    }

    public function requisitionItem() {
        return $this->belongsTo(RequisitionItem::class, 'requisition_item_id');
    }
}
