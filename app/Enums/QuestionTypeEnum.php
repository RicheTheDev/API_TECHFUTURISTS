<?php

namespace App\Enums;

enum QuestionTypeEnum: string
{
    case QCM = 'QCM';
    case Open = 'Ouvert';
    case Practical = 'Pratique';


    public static function getValues(): array
    {
        return array_map(fn($case) => $case->value, self::cases());
    }           
}