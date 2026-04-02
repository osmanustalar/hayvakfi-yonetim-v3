<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\KurbanList;
use App\Repositories\KurbanListRepository;

class KurbanListService
{
    public function __construct(
        private readonly KurbanListRepository $repository
    ) {}

    public function create(array $data): KurbanList
    {
        $data['company_id'] = (int) session('active_company_id');
        $data['created_user_id'] = auth()->id();

        /** @var KurbanList */
        $list = $this->repository->create($data);

        return $list;
    }

    public function update(KurbanList $list, array $data): KurbanList
    {
        $this->repository->update($list->id, $data);

        return $list->refresh();
    }

    public function delete(KurbanList $list): bool
    {
        return $this->repository->delete($list->id);
    }
}
