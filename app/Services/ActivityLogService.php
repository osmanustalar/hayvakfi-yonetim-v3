<?php

declare(strict_types=1);

namespace App\Services;

use Illuminate\Database\Eloquent\Model;
use Spatie\Activitylog\Facades\Activity;

class ActivityLogService
{
    public function log(
        string $description,
        ?Model $subject = null,
        array $properties = [],
    ): void {
        $companyId = session('active_company_id');

        $activity = Activity::withProperties(array_merge(
            $properties,
            ['company_id' => $companyId]
        ))->log($description);

        if ($subject) {
            $activity->subject()->associate($subject)->save();
        }
    }
}
