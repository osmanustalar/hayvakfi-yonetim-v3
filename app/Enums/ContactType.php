<?php

declare(strict_types=1);

namespace App\Enums;

enum ContactType: string
{
    case DONOR = 'donor';
    case AID_RECIPIENT = 'aid_recipient';
    case STUDENT = 'student';

    public function label(): string
    {
        return match ($this) {
            self::DONOR => 'Bağışçı',
            self::AID_RECIPIENT => 'Yardım Alan',
            self::STUDENT => 'Öğrenci',
        };
    }
}
