<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SampleBalance extends Model {

    protected $table = 'sample_balance';
    protected $guarded = [];

    public function sample_store() {
        return $this->belongsTo(SampleStore::class);
    }

}
