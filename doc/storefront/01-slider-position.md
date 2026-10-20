# Step 01 — Slider position (hero / promo)

Read `CLAUDE.md`, `testcase.md`, `doc/storefront/README.md` first.
**Start in plan mode**, wait for approval.

## Tasks

1. Migration: `sliders.position` string default `hero`, index. Enum `SliderPosition` (`hero`, `promo`) with `label()`.
2. Max 5 active **per position** (replace the global rule).
3. Preset for promo images: `promo` 1200x800 cover webp q75 + `promo_sm` 600x400 (srcset). Hero presets unchanged. Upload rule for promo: min 1200x800, max 2MB.
4. Admin sliders: position select in the form (helper text with the right image size per position), position filter tab on the list.
5. SliderService: `activeForStorefront()` returns `['hero' => [...], 'promo' => first active promo or null]`; cache keys per position, all cleared on any slider write.
6. HomeService and the Home page prop shape updated (hero list unchanged for `HeroSlider`).

## Tests

- Position stored and filtered; 6th active per position rejected, 5 hero + 5 promo allowed.
- Promo preset sizes; storefront shape; cache cleared.

## Done when

`php artisan test`, `npx tsc --noEmit`, `npx vp lint` exit 0. Commit `feat: slider positions`. Report changed files, exit codes, hash.
