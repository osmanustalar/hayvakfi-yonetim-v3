<?php

declare(strict_types=1);

namespace App\Observers;

use App\Models\Contact;
use App\Models\KurbanEntry;

class KurbanEntryObserver
{
    public function created(KurbanEntry $entry): void
    {
        Contact::where('id', $entry->contact_id)
            ->where('is_donor', false)
            ->update(['is_donor' => true]);
    }
}
