<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Models\Contact;
use Illuminate\Database\Eloquent\Collection;

class ContactRepository extends BaseRepository
{
    public function __construct()
    {
        parent::__construct(new Contact());
    }

    public function search(string $query): Collection
    {
        $term = '%' . $query . '%';

        return $this->model->newQuery()
            ->where(function ($builder) use ($term): void {
                $builder->where('phone', 'like', $term)
                    ->orWhere('national_id', 'like', $term)
                    ->orWhere('first_name', 'like', $term)
                    ->orWhere('last_name', 'like', $term);
            })
            ->get();
    }

    public function findByPhone(string $phone): ?Contact
    {
        /** @var Contact|null $result */
        $result = $this->model->newQuery()->where('phone', $phone)->first();

        return $result;
    }

    public function findByNationalId(string $nationalId): ?Contact
    {
        /** @var Contact|null $result */
        $result = $this->model->newQuery()->where('national_id', $nationalId)->first();

        return $result;
    }
}
