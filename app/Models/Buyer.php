<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Buyer extends Model {

    protected $table = 'buyers';

    public function sample_types() {
        return $this->hasMany(SampleType::class);
    }

}
