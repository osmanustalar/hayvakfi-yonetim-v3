<?php

declare(strict_types=1);

namespace App\Imports;

use App\Models\Contact;
use App\Models\ContactCategory;
use App\Models\Region;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\SkipsEmptyRows;
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\WithStartRow;

// Export sırası: ID, Ad, Soyad, Telefon, TC Kimlik, Doğum Tarihi, Adres, Bölge,
//                WhatsApp, Kategoriler, Notlar
class ContactsImport implements ToCollection, WithStartRow, SkipsEmptyRows
{
    public int $importedCount = 0;
    public int $updatedCount  = 0;
    public int $skippedCount  = 0;

    public function startRow(): int
    {
        return 2; // 1. satır başlık
    }

    public function collection(Collection $rows): void
    {
        foreach ($rows as $row) {
            $id        = $this->val($row, 0);
            $firstName = trim($this->val($row, 1) ?? '');
            $lastName  = trim($this->val($row, 2) ?? '');

            if ($firstName === '') {
                $this->skippedCount++;
                continue;
            }

            $phone      = $this->cleanPhone((string) ($this->val($row, 3) ?? ''));
            $nationalId = $this->val($row, 4) !== null ? (string) $this->val($row, 4) : null;
            $birthDate  = $this->parseDate((string) ($this->val($row, 5) ?? ''));
            $address    = $this->val($row, 6) !== null ? (string) $this->val($row, 6) : null;
            $regionId   = $this->resolveRegion((string) ($this->val($row, 7) ?? ''));
            $whatsapp   = $this->parseBool((string) ($this->val($row, 8) ?? ''));
            $categoryIds = $this->resolveCategories((string) ($this->val($row, 9) ?? ''));
            $notes      = $this->val($row, 10) !== null ? (string) $this->val($row, 10) : null;

            $data = [
                'first_name'       => $firstName,
                'last_name'        => $lastName,
                'phone'            => $phone,
                'national_id'      => $nationalId,
                'birth_date'       => $birthDate,
                'address'          => $address,
                'region_id'        => $regionId,
                'whatsapp_enabled' => $whatsapp,
                'notes'            => $notes,
            ];

            if ($id !== null && $id !== '') {
                $contact = Contact::find((int) $id);
                if ($contact !== null) {
                    $contact->update($data);
                    if (!empty($categoryIds)) {
                        $contact->categories()->sync($categoryIds);
                    }
                    $this->updatedCount++;
                    continue;
                }
            }

            $contact = Contact::create(array_merge($data, [
                'created_user_id' => auth()->id(),
            ]));

            if (!empty($categoryIds)) {
                $contact->categories()->sync($categoryIds);
            }

            $this->importedCount++;
        }
    }

    private function val(mixed $row, int $index): mixed
    {
        return $row[$index] ?? null;
    }

    private function cleanPhone(string $phone): ?string
    {
        $phone = trim($phone);
        if ($phone === '') {
            return null;
        }
        if (str_contains($phone, '/')) {
            $phone = trim(explode('/', $phone)[0]);
        }
        return $phone ?: null;
    }

    private function parseDate(string $value): ?string
    {
        $value = trim($value);
        if ($value === '') {
            return null;
        }
        // dd.mm.yyyy → yyyy-mm-dd
        if (preg_match('/^(\d{2})\.(\d{2})\.(\d{4})$/', $value, $m)) {
            return "{$m[3]}-{$m[2]}-{$m[1]}";
        }
        return null;
    }

    private function parseBool(string $value): bool
    {
        return trim($value) === 'Evet';
    }

    private function resolveRegion(string $name): ?int
    {
        $name = trim($name);
        if ($name === '') {
            return null;
        }
        return Region::firstOrCreate(
            ['name' => $name],
            ['is_active' => true, 'sort_order' => 0],
        )->id;
    }

    private function resolveCategories(string $categoryStr): array
    {
        $categoryStr = trim($categoryStr);
        if ($categoryStr === '') {
            return [];
        }

        $names = array_map('trim', explode(',', $categoryStr));
        return ContactCategory::whereIn('name', $names)
            ->pluck('id')
            ->toArray();
    }
}
