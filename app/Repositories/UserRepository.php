<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Models\User;

class UserRepository extends BaseRepository
{
    public function __construct()
    {
        parent::__construct(new User());
    }

    public function findByPhone(string $phone): ?User
    {
        return User::where('phone', $phone)->first();
    }
}
