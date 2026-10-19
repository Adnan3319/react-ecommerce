<?php

use App\Models\User;

function loginAs(string $username, string $password = 'password', array $extra = [])
{
    return test()->post(route('admin.login.store'), [
        'username' => $username,
        'password' => $password,
        ...$extra,
    ]);
}

it('shows the login page to guests', function () {
    $this->get(route('admin.login'))
        ->assertOk()
        ->assertInertia(fn ($page) => $page->component('Admin/AuthPages/SignIn', false));
});

it('logs in an active user and records last login', function () {
    $user = User::factory()->create(['username' => 'staff']);
    $sessionId = session()->getId();

    loginAs('staff')->assertRedirect(route('admin.dashboard'));

    $this->assertAuthenticatedAs($user);
    expect($user->fresh()->last_login_at)->not->toBeNull()
        ->and(session()->getId())->not->toBe($sessionId);
});

it('rejects a wrong password', function () {
    User::factory()->create(['username' => 'staff']);

    loginAs('staff', 'wrong-password')->assertSessionHasErrors('username');

    $this->assertGuest();
});

it('rejects an unknown username', function () {
    loginAs('ghost')->assertSessionHasErrors('username');

    $this->assertGuest();
});

it('rejects inactive users with the same generic error', function () {
    User::factory()->inactive()->create(['username' => 'staff']);

    loginAs('staff')->assertSessionHasErrors(['username' => 'These credentials do not match our records.']);

    $this->assertGuest();
});

it('throttles after five failed attempts per username and IP', function () {
    User::factory()->create(['username' => 'staff']);

    foreach (range(1, 5) as $ignored) {
        loginAs('staff', 'wrong-password')->assertSessionHasErrors('username');
    }

    loginAs('staff')->assertSessionHasErrors('username');
    $this->assertGuest();

    loginAs('other-user', 'wrong-password');
    $this->assertGuest();
});

it('redirects guests away from protected admin pages', function () {
    $this->get(route('admin.dashboard'))->assertRedirect(route('admin.login'));
    $this->get(route('admin.index'))->assertRedirect(route('admin.dashboard'));
    $this->followingRedirects()->get(route('admin.index'))
        ->assertInertia(fn ($page) => $page->component('Admin/AuthPages/SignIn', false));
});

it('redirects a logged-in user away from the login page', function () {
    $this->actingAs(User::factory()->create());

    $this->get(route('admin.login'))->assertRedirect(route('admin.dashboard'));
    $this->get(route('admin.index'))->assertRedirect(route('admin.dashboard'));
});

it('shares only safe user fields with the frontend', function () {
    $user = User::factory()->superAdmin()->create(['username' => 'boss']);

    $this->actingAs($user)->get(route('admin.dashboard'))
        ->assertInertia(fn ($page) => $page->where('auth.user', [
            'id' => $user->id,
            'name' => $user->name,
            'username' => 'boss',
            'role' => 'super_admin',
        ]));
});

it('logs out, invalidates the session and redirects to login', function () {
    $this->actingAs(User::factory()->create());
    $token = csrf_token();

    $this->post(route('admin.logout'))->assertRedirect(route('admin.login'));

    $this->assertGuest();
    expect(csrf_token())->not->toBe($token);
});
