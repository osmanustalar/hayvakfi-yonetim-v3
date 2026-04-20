<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Models\SchoolClass;
use Illuminate\Database\Eloquent\Collection;

class SchoolClassRepository extends BaseRepository
{
    public function __construct(SchoolClass $model)
    {
        parent::__construct($model);
    }

    public function getAllActive(): Collection
    {
        return $this->model->where('is_active', true)->orderBy('name')->get();
    }

    public function findWithEnrollments(int $id): ?SchoolClass
    {
        return $this->model->with(['enrollments.contact', 'teacher'])->find($id);
    }
}
