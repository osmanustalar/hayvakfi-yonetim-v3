<?php

declare(strict_types=1);

namespace App\Enums;

enum SacrificeType: string
{
    case VACIP  = 'vacip';
    case SADAKA = 'sadaka';

    public function label(): string
    {
        return match($this) {
            self::VACIP  => 'Vacip Kurban',
            self::SADAKA => 'Sadaka Kurban',
        };
    }

    public function categoryId(): int
    {
        return match($this) {
            self::VACIP  => 13,
            self::SADAKA => 15,
        };
    }
}
