<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\Currency;
use Illuminate\Database\Seeder;

class CurrencySeeder extends Seeder
{
    public function run(): void
    {
        $currencies = [
            ['name' => 'TL', 'symbol' => '₺', 'is_active' => true],
            ['name' => 'USD', 'symbol' => '$', 'is_active' => true],
            ['name' => 'EUR', 'symbol' => '€', 'is_active' => true],
        ];

        foreach ($currencies as $currency) {
            Currency::firstOrCreate(['name' => $currency['name']], $currency);
        }
    }
}
