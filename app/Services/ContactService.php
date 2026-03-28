<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Contact;
use App\Repositories\ContactRepository;
use Illuminate\Database\Eloquent\Collection;

class ContactService
{
    public function __construct(private readonly ContactRepository $repository) {}

    public function create(array $data): Contact
    {
        $data['created_user_id'] = auth()->id();

        /** @var Contact $contact */
        $contact = $this->repository->create($data);

        return $contact;
    }

    public function update(Contact $contact, array $data): Contact
    {
        $this->repository->update($contact->id, $data);

        return $contact->refresh();
    }

    public function delete(Contact $contact): bool
    {
        return $this->repository->delete($contact->id);
    }

    public function search(string $query): Collection
    {
        return $this->repository->search($query);
    }

    public function findByPhone(string $phone): ?Contact
    {
        return $this->repository->findByPhone($phone);
    }

    public function findByNationalId(string $nationalId): ?Contact
    {
        return $this->repository->findByNationalId($nationalId);
    }
}
