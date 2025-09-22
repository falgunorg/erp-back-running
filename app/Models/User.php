<?php

namespace App\Models;

use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable {

    use HasApiTokens,
        HasFactory,
        Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $table = 'users';
    protected $fillable = [
        'full_name',
        'email',
        'password',
        'staff_id',
        'role_permission',
        'department',
        'designation',
        'company',
        'status',
        'photo',
        'sign',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var array<int, string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'email_verified_at' => 'datetime',
    ];

    public function sample_types() {
        return $this->hasMany(SampleType::class);
    }

    public function imap_config() {
        return $this->hasOne(ImapConfig::class);
    }

    public function leaves() {
        return $this->hasMany(Leave::class, 'user_id');
    }

    public function payroll() {
        return $this->hasOne(Payroll::class, 'user_id', 'id');
    }
}
