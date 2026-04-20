<?php

declare(strict_types=1);

namespace App\Enums;

enum FeeStatus: string
{
    case PENDING = 'pending';
    case PAID = 'paid';
    case OVERDUE = 'overdue';
    case WAIVED = 'waived';

    public function label(): string
    {
        return match ($this) {
            self::PENDING => 'Bekliyor',
            self::PAID => 'Ödendi',
            self::OVERDUE => 'Gecikmiş',
            self::WAIVED => 'Muaf',
        };
    }
}
