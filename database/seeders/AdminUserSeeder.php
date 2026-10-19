<?php

namespace Database\Seeders;

use App\Enums\UserRole;
use App\Models\User;
use Illuminate\Database\Seeder;
use RuntimeException;

class AdminUserSeeder extends Seeder
{
    public function run(): void
    {
        $username = config('admin.username');
        $password = config('admin.password');

        if (blank($username) || blank($password)) {
            throw new RuntimeException('ADMIN_USERNAME and ADMIN_PASSWORD must be set in .env to seed the super admin.');
        }

        User::firstOrCreate(
            ['username' => $username],
            [
                'name' => 'Super Admin',
                'password' => $password,
                'role' => UserRole::SuperAdmin,
                'is_active' => true,
            ],
        );
    }
}
