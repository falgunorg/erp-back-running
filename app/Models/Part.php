<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Part extends Model {

    protected $table = 'parts';

    public function substores() {
        return $this->hasMany(SubStore::class, 'part_id');
    }

}
