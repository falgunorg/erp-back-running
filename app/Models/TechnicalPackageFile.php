<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;

class TechnicalPackageFile extends Model {

    protected $table = 'technical_package_files';
    protected $fillable = [
        'technical_package_id', 'filename', 'file_type'
    ];
    // ✅ This appends the custom accessor to JSON
    protected $appends = ['file_url'];

    public function technicalPackage() {
        return $this->belongsTo(TechnicalPackage::class);
    }

    // ✅ Accessor for full file URL
    public function getFileUrlAttribute() {
        return $this->filename ? asset(Storage::url('technical_packages/' . $this->filename)) : null;
    }
}
