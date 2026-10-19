<?php

use App\Enums\UserRole;
use App\Models\User;
use Database\Seeders\AdminUserSeeder;
use Illuminate\Support\Facades\Hash;

it('creates the super admin once and never resets an existing password', function () {
    config(['admin.username' => 'root', 'admin.password' => 'first-secret']);
    $this->seed(AdminUserSeeder::class);

    config(['admin.password' => 'second-secret']);
    $this->seed(AdminUserSeeder::class);

    $admin = User::where('username', 'root')->sole();

    expect(User::count())->toBe(1)
        ->and($admin->role)->toBe(UserRole::SuperAdmin)
        ->and($admin->is_active)->toBeTrue()
        ->and(Hash::check('first-secret', $admin->password))->toBeTrue();
});

it('fails loudly when credentials are missing from the environment', function () {
    config(['admin.username' => null, 'admin.password' => null]);

    $this->seed(AdminUserSeeder::class);
})->throws(RuntimeException::class);
