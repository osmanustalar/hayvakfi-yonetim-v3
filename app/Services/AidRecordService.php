<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\AidRecord;
use App\Models\Contact;
use App\Repositories\AidRecordRepository;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;

class AidRecordService
{
    public function __construct(
        private readonly AidRecordRepository $repository
    ) {}

    public function getAll(): Collection
    {
        return $this->repository->getAllWithRelations();
    }

    public function find(int $id): ?AidRecord
    {
        return $this->repository->find($id);
    }

    public function getByContact(int $contactId): Collection
    {
        return $this->repository->getByContact($contactId);
    }

    public function create(array $data): AidRecord
    {
        return DB::transaction(function () use ($data): AidRecord {
            $data['company_id'] = (int) session('active_company_id');
            $data['created_user_id'] = auth()->id();

            $aidRecord = $this->repository->create($data);

            // Yan etki: contact.is_aid_recipient = true
            Contact::where('id', $data['contact_id'])
                ->where('is_aid_recipient', false)
                ->update(['is_aid_recipient' => true]);

            return $aidRecord;
        });
    }

    public function update(int $id, array $data): bool
    {
        return DB::transaction(function () use ($id, $data): bool {
            return $this->repository->update($id, $data);
        });
    }

    public function delete(int $id): bool
    {
        return DB::transaction(function () use ($id): bool {
            return $this->repository->delete($id);
        });
    }
}
