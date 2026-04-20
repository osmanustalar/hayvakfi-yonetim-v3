<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Models\AidRecord;
use Illuminate\Database\Eloquent\Collection;

class AidRecordRepository extends BaseRepository
{
    public function __construct(AidRecord $model)
    {
        parent::__construct($model);
    }

    public function getByContact(int $contactId): Collection
    {
        return $this->model->where('contact_id', $contactId)
            ->orderBy('given_at', 'desc')
            ->get();
    }

    public function getAllWithRelations(): Collection
    {
        return $this->model->with(['contact', 'transaction', 'createdBy'])
            ->orderBy('given_at', 'desc')
            ->get();
    }
}
