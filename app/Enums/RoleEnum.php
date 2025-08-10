<?php

namespace App\Enums;

enum RoleEnum: string
{
    case Participant = 'Participant';
    case Mentor = 'Mentor';
    case Admin = 'Admin';

    public static function getValues():array
    {
        return array_column(self::cases(), 'value');
    }
}

    
