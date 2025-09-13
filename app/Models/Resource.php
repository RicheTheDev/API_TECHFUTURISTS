<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Resource extends Model
{
    use HasFactory;

    protected $fillable = [
        'title',
        'description',
        'file_url',
        'file_type',
        'uploaded_by',
        'is_published',
        'download_count',
    ];
 
    protected $casts = [
    'is_published' => 'boolean',
];

    public function setIsPublishedAttribute($value)
    {
        $this->attributes['is_published'] = filter_var($value, FILTER_VALIDATE_BOOLEAN);
    }

    /**
     * Relation vers l'utilisateur qui a uploadé la ressource.
     */
    public function user()
    {
        return $this->belongsTo(User::class, 'uploaded_by');
    }
}
