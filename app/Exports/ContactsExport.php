<?php

declare(strict_types=1);

namespace App\Exports;

use App\Models\Contact;
use Maatwebsite\Excel\Concerns\FromQuery;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;

class ContactsExport implements FromQuery, WithHeadings, WithMapping, ShouldAutoSize
{
    public function query()
    {
        return Contact::query()->with('region', 'categories')->orderBy('id');
    }

    public function headings(): array
    {
        return [
            'ID',
            'Ad',
            'Soyad',
            'Telefon',
            'TC Kimlik',
            'Doğum Tarihi',
            'Adres',
            'Bölge',
            'WhatsApp',
            'Kategoriler',
            'Notlar',
        ];
    }

    public function map($contact): array
    {
        return [
            $contact->id,
            $contact->first_name,
            $contact->last_name,
            $contact->phone,
            $contact->national_id,
            $contact->birth_date?->format('d.m.Y'),
            $contact->address,
            $contact->region?->name,
            $contact->whatsapp_enabled ? 'Evet' : 'Hayır',
            $contact->categories->pluck('name')->implode(', '),
            $contact->notes,
        ];
    }
}
