<?php

namespace App\Models;

use App\Enums\ProjectStatusEnum;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Project extends Model
{
    use HasFactory;

    protected $fillable = [
        'title',
        'description',
        'file_url',
        'file_type',
        'submitted_by',
        'status',
        'feedback',
        'submitted_at',
    ];

    protected $casts = [
        'status' => ProjectStatusEnum::class,
        'submitted_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    public function user()
    {
        return $this->belongsTo(User::class, 'submitted_by');
    }
}
