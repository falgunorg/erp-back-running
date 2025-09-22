<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SampleType extends Model {

    protected $table = 'sample_types';
    protected $guarded = [];

    public function user() {
        return $this->belongsTo(User::class);
    }

    public function buyer() {
        return $this->belongsTo(Buyer::class);
    }

}
