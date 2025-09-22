<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;

class TechnicalPackage extends Model {

    use HasFactory;

    protected $table = 'technical_packages';
    protected $fillable = [
        'po_id', 'wo_id', 'received_date', 'techpack_number', 'buyer_id',
        'buyer_style_name', 'brand', 'item_name', 'season', 'item_type',
        'department', 'description', 'company_id', 'wash_details',
        'special_operation', 'front_photo', 'back_photo'
    ];
    // 👇 This ensures accessors are included in output
    protected $appends = ['front_photo_url', 'back_photo_url'];

    public function getFrontPhotoUrlAttribute() {
        return $this->front_photo ? asset(Storage::url('technical_packages/' . $this->front_photo)) : null;
    }

    public function getBackPhotoUrlAttribute() {
        return $this->back_photo ? asset(Storage::url('technical_packages/' . $this->back_photo)) : null;
    }

    public function materials() {
        return $this->hasMany(TechnicalPackageMaterial::class);
    }

    public function files() {
        return $this->hasMany(TechnicalPackageFile::class);
    }

    public function buyer() {
        return $this->belongsTo(Buyer::class);
    }

    public function company() {
        return $this->belongsTo(Company::class);
    }

    public function user() {
        return $this->belongsTo(User::class);
    }

    public function po() {
        return $this->belongsTo(Purchase::class, 'po_id');
    }

    public function wo() {
        return $this->belongsTo(WorkOrder::class, 'wo_id');
    }
}
