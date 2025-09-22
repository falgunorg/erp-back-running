<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;

class PoFile extends Model {

    use HasFactory;

    protected $table = 'po_files';
    protected $fillable = [
        'po_id', 'filename'
    ];
    // ✅ This appends the custom accessor to JSON
    protected $appends = ['file_url'];

    // ✅ Accessor for full file URL
    public function getFileUrlAttribute() {
        return $this->filename ? asset(Storage::url('purchase_orders/' . $this->filename)) : null;
    }

    public function po() {
        return $this->belongsTo(Po::class);
    }
}
