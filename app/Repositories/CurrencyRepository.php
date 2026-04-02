<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Models\Currency;
use Illuminate\Database\Eloquent\Collection;

class CurrencyRepository extends BaseRepository
{
    public function __construct()
    {
        parent::__construct(new Currency);
    }

    public function allActive(): Collection
    {
        return Currency::where('is_active', true)->orderBy('name')->get();
    }
}
