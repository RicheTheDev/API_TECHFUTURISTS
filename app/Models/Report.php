<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use App\Enums\ReportStatusEnum;

class Report extends Model
{
    use HasFactory;

    protected $fillable = [
        'title',
        'description',
        'file_url',
        'file_type',
        'feedback',
        'submission_deadline',
        'submitted_by',
        'submitted_at',
        'status',
    ];

    protected $casts = [
        'submission_deadline' => 'datetime',
        'submitted_at' => 'datetime',
        'status' => ReportStatusEnum::class,
    ];

    public $timestamps = true; // updated_at & created_at

    public function user()
    {
        return $this->belongsTo(User::class, 'submitted_by');
    }
}
