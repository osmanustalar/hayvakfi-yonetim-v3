<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Models\User;

class UserRepository extends BaseRepository
{
    public function __construct()
    {
        parent::__construct(new User);
    }

    public function findByPhone(string $phone): ?User
    {
        // Telefon numarasını normalize et: 05XX... veya +905XX... formatlarını işle
        $normalized = $this->normalizePhone($phone);

        return User::whereRaw("REPLACE(REPLACE(phone, '+90', '0'), '-', '') = ?", [$normalized])
            ->first();
    }

    private function normalizePhone(string $phone): string
    {
        // Boşluk ve tire kaldır
        $phone = str_replace([' ', '-', '(', ')'], '', $phone);

        // +90 ile başlıyorsa 0 ile başlayan formata çevir
        if (str_starts_with($phone, '+90')) {
            $phone = '0'.substr($phone, 3);
        }

        // 0 ile başlamıyorsa 0 ekle (90 ile başlıyorsa)
        if (str_starts_with($phone, '90') && ! str_starts_with($phone, '0')) {
            $phone = '0'.substr($phone, 2);
        }

        return $phone;
    }
}
