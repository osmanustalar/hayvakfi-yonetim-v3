<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Models\ContactCategory;

class ContactCategoryRepository extends BaseRepository
{
    public function __construct(ContactCategory $model)
    {
        parent::__construct($model);
    }
}
