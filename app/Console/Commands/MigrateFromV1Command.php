<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Services\V1Migration\CategoryMigrator;
use App\Services\V1Migration\CompanyUserMigrator;
use App\Services\V1Migration\ContactMigrator;
use App\Services\V1Migration\CurrencyMigrator;
use App\Services\V1Migration\SafeGroupMigrator;
use App\Services\V1Migration\SafeMigrator;
use App\Services\V1Migration\TransactionItemMigrator;
use App\Services\V1Migration\TransactionMigrator;
use App\Services\V1Migration\UserMigrator;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class MigrateFromV1Command extends Command
{
    protected $signature = 'db:migrate-from-v1 {--fresh} {--dry-run}';

    protected $description = 'V1 (hayvakfi_yonetim) veritabanından V3\'e veri taşır';

    public function handle(): int
    {
        $dryRun = $this->option('dry-run');
        $fresh = $this->option('fresh');

        try {
            // V1 DB bağlantısını test et
            DB::connection('v1')->getPdo();
        } catch (\Exception $e) {
            $this->error("V1 veritabanına bağlanılamıyor: {$e->getMessage()}");
            $this->error('Lütfen .env dosyasında DB_V1_* değişkenlerini kontrol edin.');

            return self::FAILURE;
        }

        $this->info('V1 → V3 Veri Migrasyon Başlatılıyor');
        $this->info('');

        if ($dryRun) {
            $this->info('🔍 DRY-RUN MODU: Sadece sayım yapılacak, veri yazılmayacak');
        }
        if ($fresh && ! $dryRun) {
            $this->warn('⚠️  FRESH MODU: V3 tablolarından var olan kayıtlar silinecek!');
            if (! $this->confirm('Devam etmek istediğinizden emin misiniz?')) {
                return self::FAILURE;
            }
        }

        $this->info('');

        // Migrators'ları sırayla çalıştır
        $migrators = [
            'Currencies' => app(CurrencyMigrator::class),
            'Users' => app(UserMigrator::class),
            'Company-User' => app(CompanyUserMigrator::class),
            'Safe Groups' => app(SafeGroupMigrator::class),
            'Safes' => app(SafeMigrator::class),
            'Categories' => app(CategoryMigrator::class),
            'Contacts' => app(ContactMigrator::class),
            'Transactions' => app(TransactionMigrator::class),
            'Transaction Items' => app(TransactionItemMigrator::class),
        ];

        $totalRecords = 0;

        foreach ($migrators as $name => $migrator) {
            $count = $migrator->count();
            $totalRecords += $count;

            if ($dryRun) {
                $this->line("✓ {$name}: {$count} kayıt");
            } else {
                $this->line("  {$name}...");
                try {
                    $migrator->migrate($fresh);
                    $this->info("  ✓ {$name}: {$count} kayıt taşındı");
                } catch (\Exception $e) {
                    $this->error("  ✗ {$name} başarısız: {$e->getMessage()}");

                    return self::FAILURE;
                }
            }
        }

        $this->info('');
        if ($dryRun) {
            $this->info("📊 Toplam {$totalRecords} kayıt taşınabilir durumda");
            $this->line('');
            $this->info('Gerçek taşıma için şunu çalıştırın:');
            $this->line('  php artisan db:migrate-from-v1'.($fresh ? ' --fresh' : ''));
        } else {
            $this->info("✅ Taşıma başarıyla tamamlandı ({$totalRecords} kayıt)");
        }

        return self::SUCCESS;
    }
}
