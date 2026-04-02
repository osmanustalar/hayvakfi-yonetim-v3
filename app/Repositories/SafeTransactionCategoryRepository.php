<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Models\SafeTransactionCategory;
use Illuminate\Database\Eloquent\Collection;

class SafeTransactionCategoryRepository extends BaseRepository
{
    public function __construct()
    {
        parent::__construct(new SafeTransactionCategory);
    }

    public function forActiveCompany(): Collection
    {
        return $this->model->newQuery()
            ->where(function ($q): void {
                $q->whereNull('company_id')
                    ->orWhere('company_id', session('active_company_id'));
            })
            ->orderBy('sort_order')
            ->get();
    }

    public function forActiveCompanyWithChildren(): Collection
    {
        return $this->model->newQuery()
            ->with('children')
            ->where(function ($q): void {
                $q->whereNull('company_id')
                    ->orWhere('company_id', session('active_company_id'));
            })
            ->whereNull('parent_id')
            ->orderBy('sort_order')
            ->get();
    }
}
