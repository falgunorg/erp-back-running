<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SubStore extends Model {

    protected $table = 'sub_stores';

    public function part() {
        return $this->belongsTo(Part::class, 'part_id');
    }

    public function company() {
        return $this->belongsTo(Company::class, 'company_id');
    }

    public function receives() {
        return $this->hasMany(SubStoreReceive::class, 'substore_id');
    }

    public function issues() {
        return $this->hasMany(SubStoreIssue::class, 'substore_id');
    }

}
