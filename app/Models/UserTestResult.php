<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class UserTestResult extends Model
{
    protected $fillable = [
        'score',
        'user_id',
        'test_id',
        'file_url',
        'file_type',
        'completed_at',
    ];

    protected $casts = [
        'completed_at' => 'datetime',
        'score' => 'float',
    ];

    /**
     * Relation avec l'utilisateur ayant passé le test.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Relation avec le test correspondant.
     */
    public function test(): BelongsTo
    {
        return $this->belongsTo(Test::class);
    }
}
