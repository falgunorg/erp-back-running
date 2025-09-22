<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class MailConfig extends Model {

    protected $table = 'mail_configs';

    public function user() {
        return $this->belongsTo(User::class);
    }

}
