<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class WorkOrder extends Model {

    use HasFactory;

    protected $table = 'workorders';
    protected $fillable = [
        'user_id',
        'wo_number',
        'technical_package_id',
        'create_date',
        'release_date',
        'delivery_date',
        'wo_ref',
        'sewing_sam',
    ];

    // ✅ Fixed typo from teckpack() to techpack()
    public function techpack() {
        return $this->belongsTo(TechnicalPackage::class, 'technical_package_id');
    }

    public function costing() {
        return $this->hasOne(Costing::class, 'technical_package_id', 'technical_package_id');
    }

    public function pos() {
        return $this->hasMany(Po::class, 'wo_id');
    }

    public function user() {
        return $this->belongsTo(User::class);
    }
}
