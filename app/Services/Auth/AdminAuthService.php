<?php

namespace App\Services\Auth;

use App\Models\User;
use Illuminate\Auth\AuthManager;
use Illuminate\Contracts\Auth\StatefulGuard;
use Illuminate\Support\Facades\Session;

class AdminAuthService
{
    public function __construct(private readonly AuthManager $auth) {}

    /**
     * Attempt to sign in an active staff user. Inactive users fail exactly like
     * wrong credentials so account state is never disclosed.
     */
    public function login(string $username, string $password, bool $remember): bool
    {
        $guard = $this->guard();

        $authenticated = $guard->attempt([
            'username' => $username,
            'password' => $password,
            'is_active' => true,
        ], $remember);

        if (! $authenticated) {
            return false;
        }

        /** @var User $user */
        $user = $guard->user();
        $user->forceFill(['last_login_at' => now()])->saveQuietly();

        return true;
    }

    public function logout(): void
    {
        $this->guard()->logout();

        Session::invalidate();
        Session::regenerateToken();
    }

    private function guard(): StatefulGuard
    {
        return $this->auth->guard('web');
    }
}
