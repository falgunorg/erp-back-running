<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Part extends Model {

    use HasFactory;

    protected $table = 'parts';
    protected $fillable = [
        'user_id',
        'company_id',
        'title',
        'type',
        'unit',
        'min_balance',
        'brand',
        'model',
        'photo',
    ];
    // Append custom attribute when the model is serialized
    protected $appends = ['image_source'];

    // Accessor for image_source
    public function getImageSourceAttribute() {
        return $this->photo ? url('/parts/' . $this->photo) : null;
    }

    public function company() {
        return $this->belongsTo(Company::class, 'company_id');
    }

    public function user() {
        return $this->belongsTo(User::class, 'user_id');
    }
}
