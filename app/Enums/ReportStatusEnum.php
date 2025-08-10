<?php

namespace App\Enums;

enum ReportStatusEnum: string
{
    case Submitted = 'Soumis';
    case InReview = 'En Revue';
    case Approved = 'Approuvé';
    case Rejected = 'Rejeté';

    public static function getValues(): array
    {
        return array_map(fn($case) => $case->value, self::cases());
    }
}
