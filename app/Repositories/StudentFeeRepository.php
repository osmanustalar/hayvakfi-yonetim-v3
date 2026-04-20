<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Enums\FeeStatus;
use App\Models\StudentFee;
use Illuminate\Database\Eloquent\Collection;

class StudentFeeRepository extends BaseRepository
{
    public function __construct(StudentFee $model)
    {
        parent::__construct($model);
    }

    public function findByEnrollmentAndPeriod(int $enrollmentId, string $period): ?StudentFee
    {
        return $this->model->where('enrollment_id', $enrollmentId)
            ->where('period', $period)
            ->first();
    }

    public function getPendingFees(): Collection
    {
        return $this->model->where('status', FeeStatus::PENDING->value)
            ->with(['enrollment.contact', 'enrollment.schoolClass'])
            ->orderBy('due_date')
            ->get();
    }

    public function getOverdueFees(): Collection
    {
        return $this->model->where('status', FeeStatus::OVERDUE->value)
            ->with(['enrollment.contact', 'enrollment.schoolClass'])
            ->orderBy('due_date')
            ->get();
    }

    public function getByEnrollment(int $enrollmentId): Collection
    {
        return $this->model->where('enrollment_id', $enrollmentId)
            ->orderBy('period', 'desc')
            ->get();
    }
}
