<?php

declare(strict_types=1);

namespace App\Traits;

use App\Models\Scopes\CompanyScope;

trait HasCompanyScope
{
    protected static function bootHasCompanyScope(): void
    {
        static::addGlobalScope(new CompanyScope());
    }
}
