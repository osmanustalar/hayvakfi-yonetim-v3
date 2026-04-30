<?php

declare(strict_types=1);

namespace App\Observers;

use App\Enums\ContactType;
use App\Models\Contact;
use App\Models\ContactCategory;
use App\Models\KurbanEntry;

class KurbanEntryObserver
{
    public function created(KurbanEntry $entry): void
    {
        if ($entry->contact_id === null) {
            return;
        }

        $donorCategory = ContactCategory::findByType(ContactType::DONOR);
        if ($donorCategory === null) {
            return;
        }

        Contact::findOrFail($entry->contact_id)
            ->categories()
            ->syncWithoutDetaching([$donorCategory->id]);
    }
}
