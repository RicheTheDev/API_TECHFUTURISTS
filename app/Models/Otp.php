<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;

class Otp extends Model
{
    protected $table = 'otps';

    protected $fillable = [
        'email',
        'code',
        'expires_at',
    ];

    public $timestamps = false; // car pas de updated_at

    protected $casts = [
        'created_at' => 'datetime',
        'expires_at' => 'datetime',
    ];

    public static function boot()
    {
        parent::boot();

        static::creating(function ($otp) {
            $otp->expires_at = now()->addMinutes(10);
        });
    }

    public function isExpired(): bool
    {
        return now()->greaterThan($this->expires_at);
    }
}
