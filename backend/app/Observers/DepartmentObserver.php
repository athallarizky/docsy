<?php

namespace App\Observers;

use App\Models\Department;
use App\Support\CacheInvalidations;

class DepartmentObserver
{
    public function created(Department $department): void
    {
        CacheInvalidations::departmentChanged();
    }

    public function updated(Department $department): void
    {
        CacheInvalidations::departmentChanged();
    }

    public function deleted(Department $department): void
    {
        CacheInvalidations::departmentChanged();
    }
}
