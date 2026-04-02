<?php

declare(strict_types=1);

namespace App\Enums;

enum OperationType: string
{
    case EXCHANGE = 'exchange';
    case TRANSFER = 'transfer';

    public function label(): string
    {
        return match ($this) {
            self::EXCHANGE => 'Döviz İşlemi',
            self::TRANSFER => 'Transfer',
        };
    }
}
