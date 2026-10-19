# Admin Authentication

Status: **DONE** (backend). Staff-only session login. No registration, no customer auth, no API tokens.

## Overview
- Guard: `web` (session). Sanctum stateful is enabled (`statefulApi()`), but no tokens are used.
- `users` table holds admin/staff only.
- Login is by `username` + `password` (+ optional `remember`).

## Users table
| Column | Notes |
|---|---|
| `username` | string(50), unique, used to log in |
| `email` | nullable, unique |
| `role` | `App\Enums\UserRole`: `super_admin`, `admin` (default `admin`) |
| `is_active` | boolean, default true. Inactive users cannot log in |
| `last_login_at` | nullable timestamp, set on every successful login |

Soft deletes and `created_by` / `updated_by` / `deleted_by` are kept. The old `status` column was removed (replaced by `is_active`).

## Flow
`Admin\AuthController` (thin) → `LoginRequest` (validation + rate limit) → `App\Services\Auth\AdminAuthService`.

- **Login**: inactive users and wrong credentials return the same generic error (no account enumeration). The guard regenerates the session id on success, and `last_login_at` is updated.
- **Rate limit**: 5 failed attempts per minute, keyed by `username|IP`. A successful login clears the counter.
- **Logout**: guard logout, session invalidate, CSRF token regenerate, redirect to login.

## Routes
| Method | URL | Name | Middleware |
|---|---|---|---|
| GET | `/admin` | `admin.index` | redirects to dashboard |
| GET | `/admin/login` | `admin.login` | guest |
| POST | `/admin/login` | `admin.login.store` | guest |
| POST | `/admin/logout` | `admin.logout` | auth |
| GET | `/admin/dashboard` | `admin.dashboard` | auth |

All other `/admin/*` pages are behind `auth`. In `bootstrap/app.php`, `redirectGuestsTo` sends guests to `admin.login` and `redirectUsersTo` sends logged-in users to `admin.dashboard` (for `admin` / `admin/*`). Other paths fall back to `home`.

## Shared Inertia props
`auth.user` = `{ id, name, username, role }` or `null`. Nothing else from the user is exposed.

## Seeding the first super admin
Set in `.env` (keys are in `.env.example`, values left empty):

```
ADMIN_USERNAME=
ADMIN_PASSWORD=
```

Then run `php artisan db:seed` (or `--class=AdminUserSeeder`). Values are read through `config/admin.php`. The seeder is idempotent: it creates the user only if the username is missing and never resets an existing password. It throws if either value is empty.

## Frontend
- `SignInForm.tsx` posts to the Wayfinder route `@/routes/admin/login` (`store`) using `username`.
- `UserDropdown.tsx` shows `username` and logs out via the Wayfinder `logout` route.
- Wayfinder files are generated: run `php artisan wayfinder:generate` after route changes.

## Tests
`tests/Feature/Admin/AuthTest.php` and `AdminUserSeederTest.php` cover: login success and failure, inactive user, throttle, guest redirects, logged-in redirect from login, shared props, logout, and seeder idempotency.

## Changed / added files
- Migration `0001_01_01_000000_create_users_table.php` (edited in place; use `migrate:fresh`)
- `app/Enums/UserRole.php`, `app/Models/User.php`, `database/factories/UserFactory.php`
- `config/admin.php`, `database/seeders/AdminUserSeeder.php`, `database/seeders/DatabaseSeeder.php`, `.env.example`
- `app/Http/Requests/Admin/LoginRequest.php`, `app/Services/Auth/AdminAuthService.php`, `app/Http/Controllers/Admin/AuthController.php` (old `AdminAuthController` deleted)
- `routes/web.php`, `bootstrap/app.php`, `app/Http/Middleware/HandleInertiaRequests.php`
- `resources/js/admin/components/auth/SignInForm.tsx`, `resources/js/admin/components/header/UserDropdown.tsx`
- `tests/Feature/Admin/AuthTest.php`, `tests/Feature/Admin/AdminUserSeederTest.php`
