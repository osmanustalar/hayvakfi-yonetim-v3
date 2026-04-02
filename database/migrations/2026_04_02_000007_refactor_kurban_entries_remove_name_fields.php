<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Önce NULL olan contact_id kayıtları için Contact oluştur
        DB::statement("
            INSERT INTO contacts (first_name, last_name, phone, is_donor, created_user_id, created_at, updated_at)
            SELECT
                ke.first_name,
                ke.last_name,
                ke.phone,
                true,
                ke.created_user_id,
                ke.created_at,
                ke.updated_at
            FROM kurban_entries ke
            WHERE ke.contact_id IS NULL
            AND NOT EXISTS (
                SELECT 1 FROM contacts c
                WHERE c.phone = ke.phone
                AND ke.phone IS NOT NULL
            )
            GROUP BY ke.phone, ke.first_name, ke.last_name, ke.created_user_id, ke.created_at, ke.updated_at
        ");

        // Telefon eşleşmesi olanları güncelle
        DB::statement("
            UPDATE kurban_entries ke
            INNER JOIN contacts c ON c.phone = ke.phone
            SET ke.contact_id = c.id
            WHERE ke.contact_id IS NULL
            AND ke.phone IS NOT NULL
        ");

        // İsim-soyad eşleşmesi olanları güncelle (telefon yoksa)
        DB::statement("
            UPDATE kurban_entries ke
            INNER JOIN (
                SELECT MIN(c.id) as contact_id, c.first_name, c.last_name
                FROM contacts c
                GROUP BY c.first_name, c.last_name
            ) c ON c.first_name = ke.first_name AND c.last_name = ke.last_name
            SET ke.contact_id = c.contact_id
            WHERE ke.contact_id IS NULL
        ");

        // Hala NULL olanlar için default contact oluştur ve ata
        $defaultContact = DB::table('contacts')->insertGetId([
            'first_name'       => 'Bilinmeyen',
            'last_name'        => 'Kişi',
            'is_donor'         => true,
            'created_user_id'  => 1,
            'created_at'       => now(),
            'updated_at'       => now(),
        ]);

        DB::statement("
            UPDATE kurban_entries
            SET contact_id = {$defaultContact}
            WHERE contact_id IS NULL
        ");

        // Önce alanları kaldır
        Schema::table('kurban_entries', function (Blueprint $table): void {
            $table->dropColumn(['first_name', 'last_name', 'phone']);
        });

        // Sonra FK'yi değiştir (iki aşamada, çünkü Laravel bazı DB'lerde sorun çıkarabiliyor)
        try {
            DB::statement('ALTER TABLE kurban_entries DROP FOREIGN KEY kurban_entries_contact_id_foreign');
        } catch (\Exception $e) {
            // FK yoksa devam et
        }

        Schema::table('kurban_entries', function (Blueprint $table): void {
            $table->unsignedBigInteger('contact_id')->nullable(false)->change();
            $table->foreign('contact_id')->references('id')->on('contacts')->restrictOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('kurban_entries', function (Blueprint $table): void {
            $table->string('first_name', 100)->after('kurban_list_id');
            $table->string('last_name', 100)->after('first_name');
            $table->string('phone', 20)->nullable()->after('last_name');

            $table->dropForeign(['contact_id']);
            $table->foreignId('contact_id')->nullable()->change();
            $table->foreign('contact_id')->references('id')->on('contacts')->nullOnDelete();
        });
    }
};
