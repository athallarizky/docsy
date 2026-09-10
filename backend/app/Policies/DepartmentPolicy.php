<?php

namespace App\Policies;

use App\Models\Department;
use App\Models\User;

class DepartmentPolicy {
  /**
  * List & Detail: All Authenticated User
  */
  public function viewAny(?User $user): bool {
    return true;
  }

  public function view(?User $user, Department $department): bool {
    return true;
  }

  /**
  * Only administrator. can mutate data.
  */
  public function create(User $user): bool {
    return $user->isAdmin();
  }

  public function update(User $user, Department $department): bool {
    return $user->isAdmin();
  }

  public function delete(User $user, Department $department): bool {
    return $user->isAdmin();
  }
}