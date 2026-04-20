<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Models\StudentEnrollment;
use Illuminate\Database\Eloquent\Collection;

class StudentEnrollmentRepository extends BaseRepository
{
    public function __construct(StudentEnrollment $model)
    {
        parent::__construct($model);
    }

    public function getAllActive(): Collection
    {
        return $this->model->where('is_active', true)
            ->with(['contact', 'schoolClass'])
            ->orderBy('enrollment_date', 'desc')
            ->get();
    }

    public function findByClassAndContact(int $classId, int $contactId): ?StudentEnrollment
    {
        return $this->model->where('class_id', $classId)
            ->where('contact_id', $contactId)
            ->first();
    }

    public function getByClass(int $classId): Collection
    {
        return $this->model->where('class_id', $classId)
            ->with('contact')
            ->orderBy('enrollment_date')
            ->get();
    }
}
