<?php

declare(strict_types=1);

namespace App\Services\V1Migration;

use App\Models\Contact;

class ContactMigrator extends BaseMigrator
{
    public function count(): int
    {
        return $this->v1()->table('donors')->count();
    }

    public function migrate(bool $fresh = false): void
    {
        if ($fresh) {
            $this->truncate('contacts');
        }

        $v1Donors = $this->v1()->table('donors')->get();

        foreach ($v1Donors as $v1Donor) {
            // Name'i first_name ve last_name'e ayır
            $nameParts = explode(' ', trim($v1Donor->name), 2);
            $firstName = $nameParts[0];
            $lastName = $nameParts[1] ?? '';

            // Çoklu telefon var mı kontrol et
            $v1Phones = $this->v1()
                ->table('donor_phones')
                ->where('donor_id', $v1Donor->id)
                ->orderBy('id')
                ->get();

            $phone = null;
            $additionalPhones = [];

            foreach ($v1Phones as $idx => $phoneRecord) {
                $fullPhone = ($phoneRecord->phone_code ?? '+90') . ($phoneRecord->phone_number ?? '');
                if ($idx === 0) {
                    $phone = $fullPhone;
                } else {
                    $additionalPhones[] = $fullPhone;
                }
            }

            // Notes alanına diğer telefonları ekle
            $notes = $v1Donor->description ?? '';
            if ($additionalPhones) {
                $phoneList = implode(', ', $additionalPhones);
                $notes .= "\n\nDiğer telefonlar: {$phoneList}";
            }

            Contact::updateOrCreate(
                ['id' => $v1Donor->id],
                [
                    'first_name' => $firstName,
                    'last_name' => $lastName,
                    'phone' => $phone,
                    'address' => $v1Donor->address,
                    'city' => $v1Donor->location,
                    'notes' => trim($notes) ?: null,
                    'is_donor' => true,
                    'is_aid_recipient' => false,
                    'is_student' => false,
                    'created_user_id' => $v1Donor->created_user_id,
                    'created_at' => $v1Donor->created_at,
                    'updated_at' => $v1Donor->updated_at,
                    'deleted_at' => $v1Donor->deleted_at,
                ]
            );
        }
    }
}
