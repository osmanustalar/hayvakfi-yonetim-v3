<?php

declare(strict_types=1);

namespace App\Enums;

enum TransactionType: string
{
    case INCOME  = 'income';
    case EXPENSE = 'expense';

    public function label(): string
    {
        return match($this) {
            self::INCOME  => 'Gelir',
            self::EXPENSE => 'Gider',
        };
    }
}
