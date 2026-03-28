<?php

declare(strict_types=1);

namespace App\Services;

use App\Repositories\UserRepository;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;

class AuthService
{
    public function __construct(private readonly UserRepository $repository) {}

    public function login(string $phone, string $password, int $companyId): bool
    {
        $user = $this->repository->findByPhone($phone);

        if (! $user) {
            return false;
        }

        if (! $user->can_login || ! $user->is_active) {
            return false;
        }

        if (! Hash::check($password, $user->password)) {
            return false;
        }

        $hasCompany = $user->companies()->where('companies.id', $companyId)->exists();
        if (! $hasCompany) {
            return false;
        }

        Auth::login($user);
        session(['active_company_id' => $companyId]);

        return true;
    }

    public function logout(): void
    {
        session()->forget('active_company_id');
        Auth::logout();
    }
}
