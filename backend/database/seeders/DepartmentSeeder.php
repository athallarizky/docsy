<?php

namespace Database\Seeders;

use App\Models\Department;
use Illuminate\Database\Seeder;

class DepartmentSeeder extends Seeder {
  public function run(): void {
    $departments = [
      ['name' => 'Human Resources', 'description' => 'HR, recruitment & personnel files'],
      ['name' => 'Finance',         'description' => 'Accounting, tax & financial reports'],
      ['name' => 'IT',              'description' => 'Technical documentation & runbooks'],
      ['name' => 'Legal',           'description' => 'Contracts & compliance documents'],
    ];

    foreach($departments as $dept) {
      Department::updateOrCreate(['name' => $dept['name']], $dept);
    }
  }
}