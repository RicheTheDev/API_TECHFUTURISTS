<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use App\Enums\TestTypeEnum;

class Test extends Model
{
    protected $fillable = [
        'title',
        'description',
        'file_url',
        'file_type',
        'type',
        'created_by',
    ];

    // Cast 'type' en string (car Laravel ne supporte pas encore enum natif en DB)
    protected $casts = [
        'type' => 'string',
    ];

    /**
     * Relation avec l'utilisateur qui a créé le test.
     */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
