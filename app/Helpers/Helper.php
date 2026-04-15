<?php

declare(strict_types=1);

namespace App\Helpers;

class Helper
{
    public static function formatSaveMoney(string|float|int $amount): float
    {
        if (is_string($amount)) {
            $amount = str_replace('.', '', $amount);
            $amount = str_replace(',', '.', $amount);
        }

        return (float) $amount;
    }

    public static function formatShowMoney(string|float|int $amount): string
    {
        return number_format((float) $amount, 2, ',', '.');
    }
}
