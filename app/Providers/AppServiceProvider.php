<?php

declare(strict_types=1);

namespace App\Providers;

use App\Models\KurbanEntry;
use App\Models\SafeTransaction;
use App\Observers\KurbanEntryObserver;
use App\Observers\SafeTransactionObserver;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        SafeTransaction::observe(SafeTransactionObserver::class);
        KurbanEntry::observe(KurbanEntryObserver::class);
    }
}
