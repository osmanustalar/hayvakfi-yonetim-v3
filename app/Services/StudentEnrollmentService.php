<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Contact;
use App\Models\SchoolClass;
use App\Models\StudentEnrollment;
use App\Repositories\StudentEnrollmentRepository;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;

class StudentEnrollmentService
{
    public function __construct(
        private readonly StudentEnrollmentRepository $repository
    ) {}

    public function getAll(): Collection
    {
        return $this->repository->all();
    }

    public function getAllActive(): Collection
    {
        return $this->repository->getAllActive();
    }

    public function find(int $id): ?StudentEnrollment
    {
        return $this->repository->find($id);
    }

    public function create(array $data): StudentEnrollment
    {
        return DB::transaction(function () use ($data): StudentEnrollment {
            $data['company_id'] = (int) session('active_company_id');

            $existing = $this->repository->findByClassAndContact(
                (int) $data['class_id'],
                (int) $data['contact_id']
            );
            if ($existing !== null) {
                throw new \Exception('Bu öğrenci zaten bu sınıfa kayıtlı.');
            }

            $class = SchoolClass::findOrFail((int) $data['class_id']);
            if ($class->capacity !== null) {
                $activeCount = $this->repository->getByClass($class->id)
                    ->where('is_active', true)
                    ->count();
                if ($activeCount >= $class->capacity) {
                    throw new \Exception("Sınıf kapasitesi dolu ({$activeCount}/{$class->capacity}).");
                }
            }

            $enrollment = $this->repository->create($data);

            Contact::where('id', $data['contact_id'])
                ->where('is_student', false)
                ->update(['is_student' => true]);

            return $enrollment;
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
