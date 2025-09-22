<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PoItem extends Model {

    use HasFactory;

    protected $table = 'po_items';
    protected $fillable = [
        'po_id', 'color', 'size', 'inseam', 'qty',
        'fob', 'total'
    ];

    public function po() {
        return $this->belongsTo(Po::class);
    }
}
