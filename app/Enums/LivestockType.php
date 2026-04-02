<?php

declare(strict_types=1);

namespace App\Enums;

enum LivestockType: string
{
    case SMALL = 'small';
    case LARGE = 'large';

    public function label(): string
    {
        return match ($this) {
            self::SMALL => 'Küçük Baş',
            self::LARGE => 'Büyük Baş',
        };
    }
}
