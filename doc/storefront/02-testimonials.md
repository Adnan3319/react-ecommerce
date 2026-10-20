# Step 02 — Testimonials

Read `CLAUDE.md`, `testcase.md`, `doc/storefront/README.md` first.
**Start in plan mode**, wait for approval.

## Why admin-managed

Testimonials shown on the live store must be real customer feedback (e.g. Facebook comments or messages, with the customer's permission). Invented reviews must never reach production.

## Tasks

1. Migration + model `Testimonial` per README §2; enum `TestimonialSource`.
2. Admin CRUD (same pattern as Categories/Sliders): list with status toggle and sort order, create/edit modal (name, text with counter, rating 1–5 stars input, source select, status), delete via ConfirmDialog. Sidebar entry "Testimonials".
3. `TestimonialService::activeForStorefront()` — max 6, ordered by sort_order, cached as plain arrays, cleared on write. Add `testimonials` to HomeService.
4. Seeder: `TestimonialSampleSeeder` with 3 clearly fake entries (`[Sample] …` in the name) called **only** when `app()->environment('local')`. `DatabaseSeeder` must not call it in production. Add a test proving production seeding creates no testimonials.

## Tests

- CRUD, validation (rating 1–5, text ≤ 500, source enum), access matrix, cache.
- Production environment seeding → 0 testimonials; local → 3.

## Done when

`php artisan test`, `npx tsc --noEmit`, `npx vp lint` exit 0. Commit `feat: testimonials`. Report changed files, exit codes, hash.
