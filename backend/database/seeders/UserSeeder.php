<?php

namespace Database\Seeders;

use App\Enums\RoleEnum;
use App\Models\User;
use Illuminate\Database\Seeder;

class UserSeeder extends Seeder
{
    public function run(): void
    {
        User::updateOrCreate(
            ['email' => 'admin@example.com'],
            [
                'name' => 'System Administrator',
                'password' => 'password',
                'role' => RoleEnum::ADMINISTRATOR,
            ],
        );

        User::updateOrCreate(
            ['email' => 'viewer@example.com'],
            [
                'name' => 'Demo Viewer',
                'password' => 'password',
                'role' => RoleEnum::VIEWER,
            ],
        );
    }
}
