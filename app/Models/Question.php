<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Question extends Model
{
    protected $fillable = [
        'text',
        'type',
        'options',
        'correct_answer',
        'file_url',
        'file_type',
        'test_id',
    ];

    protected $casts = [
        'options' => 'array',
        'type' => 'string',
    ];

    /**
     * Relation avec le test auquel appartient la question.
     */
    public function test(): BelongsTo
    {
        return $this->belongsTo(Test::class);
    }
}
