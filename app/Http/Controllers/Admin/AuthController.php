<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\LoginRequest;
use App\Services\Auth\AdminAuthService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;

class AuthController extends Controller
{
    public function __construct(private readonly AdminAuthService $auth) {}

    public function showLogin(): Response
    {
        return Inertia::render('Admin/AuthPages/SignIn');
    }

    public function login(LoginRequest $request): RedirectResponse
    {
        $request->ensureIsNotRateLimited();

        $authenticated = $this->auth->login(
            $request->string('username')->toString(),
            $request->string('password')->toString(),
            $request->boolean('remember'),
        );

        if (! $authenticated) {
            $request->hitRateLimiter();

            throw ValidationException::withMessages([
                'username' => 'These credentials do not match our records.',
            ]);
        }

        $request->clearRateLimiter();

        return redirect()->intended(route('admin.dashboard'));
    }

    public function logout(): RedirectResponse
    {
        $this->auth->logout();

        return redirect()->route('admin.login');
    }
}
