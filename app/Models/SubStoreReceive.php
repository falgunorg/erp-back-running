<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SubStoreReceive extends Model {

    protected $table = 'sub_store_receives';

    public function substore() {
        return $this->belongsTo(SubStore::class, 'substore_id');
    }

    public function part() {
        return $this->belongsTo(Part::class, 'part_id');
    }

}
