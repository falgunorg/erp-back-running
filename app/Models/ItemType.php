<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ItemType extends Model {

    protected $table = 'item_types';

    public function items() {
        return $this->hasMany(Item::class);
    }
}
