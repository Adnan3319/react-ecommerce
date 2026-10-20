# E-commerce Storefront — Laravel 13 + Inertia React

## Tech Stack (Fixed — never assume otherwise)
- Backend: Laravel 13, PHP 8.3+, MySQL 8.4 (InnoDB, utf8mb4)
- Frontend: Inertia 3 + React 19 (React Compiler) + TypeScript strict + Tailwind 4 + Vite+
- Installed helpers: Wayfinder (typed routes/actions), swiper, clsx, tailwind-merge, i18next (admin only for now)
- Admin UI: TailAdmin (`resources/js/admin`, `admin.css`); storefront uses `app.css` — keep separate
- Standard Laravel structure — no module packages, no Repository layer
- Don't install new packages without asking

## Hosting Constraints (Namecheap cPanel shared hosting)
- No SSR, no Redis, no long-running processes
- Cache + session driver: `database`
- Queues: `database` driver, processed by cron `schedule:run` → `queue:work --stop-when-empty`
- Assets built locally/CI (`npm run build`), never on the server; document root = `public/`
- Cloudflare in front for static asset caching
- Code must stay SSR-ready (see Frontend Rules) so moving to a VPS only requires config
- Deploy steps: build assets locally (`npm run build`), upload, then on the server `php artisan migrate --force` and `php artisan optimize` (caches config, routes, events and views; re-run after every deploy)

## Engineering Standard
Senior-level, production-grade code only. No tutorial-style shortcuts.

## Business Scope
- Single vendor, physical products (variants + stock), English only
- Payment: Cash on Delivery only. `PaymentMethod` enum has only `cod`; gateways added later without changing order flow
- Guest ordering: name, phone, address — no login required
- Two order flows, both handled by one `CheckoutService`:
  1. Order Now — order form on the product page (single product, does not touch the cart)
  2. Cart — multiple products, order form on the Cart page
- No separate checkout page
- Delivery charge is configured in admin and calculated by the backend only

## Backend Architecture
- Layering: Controller → FormRequest → Service → Model → Resource
  - Controllers: no queries, no business logic — validate, call a Service, return a response
  - Services: business logic and Eloquent queries; no HTTP request/response handling
  - Reusable query constraints go in Model local scopes
- Group by domain inside each layer: `app/Http/Controllers/{Domain}`, `app/Http/Requests/{Domain}`, `app/Http/Resources/{Domain}`, `app/Services/{Domain}`, plus `app/Models`, `app/Enums`, `app/Policies`, `app/Jobs`
- Storefront controllers return `Inertia::render()` with Resource-shaped props and a `seo` prop on every indexable page
- Admin and storefront pages never call JSON APIs: reads via Inertia props, writes via Inertia form submit → redirect back with flash
- `App\Http\Responses\ApiResponse` is only for non-page endpoints (search suggestions, webhooks, future mobile app) — never invent another shape
- Exception rendering centralized in `bootstrap/app.php` (`withExceptions`)
- Soft deletes on master data (products, categories, customers)

## E-commerce Rules (Non-negotiable)
- Money stored as integer minor units — never floats
- Prices, delivery charge and totals always recalculated server-side; never trust client amounts
- Order status and payment status are Enums with explicit allowed transitions
  - Order: pending → confirmed → processing → shipped → delivered | cancelled | returned
  - Payment: unpaid → paid
- Order placement runs in `DB::transaction()` with `lockForUpdate()` on stock rows; stock released on cancel/return
- Order lines snapshot product name, SKU, variant and unit price
- Orders store `source` (`buy_now` | `cart`) and captured UTM params / fbclid
- Phone validated as Bangladeshi mobile (`01XXXXXXXXX`); customers matched by phone
- Anti-abuse: rate limit order endpoints; throttle repeated orders from the same phone
- Cart is server-side (session for guests); cart count exposed via Inertia shared props
- Emails/notifications are queued — never on the order request path

## SEO Rules
- Clean URLs: `/products/{slug}`, `/categories/{slug}`; 301 redirect on slug change
- Every indexable page sends a `seo` prop (title, description, canonical, image, JSON-LD)
- `storefront.blade.php` renders these meta tags server-side (first load, crawlers, social previews); `shared/components/Seo.tsx` updates them on client navigation — same data source
- JSON-LD: Product (price, availability), BreadcrumbList, Organization
- Auto-generated cached `sitemap.xml` and `robots.txt`
- Filter/sort/search result URLs use canonical or `noindex`; order success page is `noindex`
- Semantic HTML, one `h1` per page, descriptive image `alt`, real links for pagination

## Frontend Structure
```
resources/views/
├── storefront.blade.php     # root view + server-rendered SEO meta
└── admin.blade.php
resources/js/
├── storefront/
│   ├── app.tsx              # entry: lazy-glob ./pages/** only + default layout
│   ├── ssr.tsx              # unused until VPS; keep compiling
│   ├── pages/               # thin pages: Home, Products/{Index,Show}, Categories/Show,
│   │                        # Cart, Orders/Success, Search, Pages/Show, Errors/NotFound
│   ├── features/{name}/     # product, cart, order, category, search, home — one flat folder each
│   │                        # (PascalCase.tsx, useThing.ts, types.ts, constants.ts)
│   ├── components/
│   │   ├── ui/              # storefront primitives: Button, Input, Badge, Image, Skeleton, ...
│   │   └── ...              # storefront-wide: Header, Footer, MobileNav, Breadcrumbs
│   └── layouts/StorefrontLayout.tsx
├── admin/                   # TailAdmin, own entry
│   ├── features/{name}/     # categories, sliders, products, ... — one flat folder each
│   ├── components/ui/       # TailAdmin primitives: Button, Modal, Pagination, ... (+ form/)
│   ├── components/common/   # admin components used by 2+ features: StatusToggle, ConfirmDialog, FlashMessage
│   └── pages/               # thin pages
├── shared/                  # NO UI components
│   ├── components/Seo.tsx   # head/meta only
│   ├── hooks/  lib/  types/  constants/
├── actions/  routes/        # Wayfinder generated — never edit
└── types/                   # global .d.ts
```

## Frontend Rules
- Dependency direction: pages → features → shared (storefront `components/`/`layouts` may use features). `admin` and `storefront` never import each other; `shared` imports neither. Enforced with Oxlint `no-restricted-imports` in `vite.config.ts`
- Admin and storefront never share UI components: admin uses TailAdmin styling (`admin/components/ui`), storefront has its own design (`storefront/components/ui`). Reuse the existing primitives there; never create duplicates
- Features never import another feature's files. Anything used by 2+ features moves to `admin/components/common` or `storefront/components`
- Pages stay thin (~50 lines max); UI and logic live in features
- UI primitives (`*/components/ui`) are props-only, no business logic; domain-aware components live in the feature folder
- `OrderForm` is one component reused by the product page and the Cart page
- All page data comes from Inertia props — no fetch-on-mount for page content, no hardcoded content arrays
- Interactive updates via Inertia (`router`, partial reloads, deferred props, prefetch) — no TanStack Query/Zustand
- Auth/cart/shared data from `usePage().props`
- Persistent layouts (`Page.layout`); never wrap a page in its layout
- Wayfinder for every URL — no hardcoded paths
- Forms via Inertia `useForm`; client validation is UX only, backend is the source of truth
- TypeScript strict, no `any`; shared types in `shared/types`
- Aliases: `@/storefront/*`, `@/admin/*`, `@/shared/*`
- Structure: one flat folder per feature — `PascalCase.tsx` components, `useThing.ts` hooks, `types.ts`, `constants.ts`; no per-component folders, no `index.ts` barrels (import files directly). Add a `components/` subfolder only when a feature exceeds ~10 files
- No class-name constants: use the TailAdmin components in `admin/components/ui` (and `form/`) instead of repeating Tailwind class strings. `constants.ts` holds real constants only (status values, limits)
- Naming: hooks `useThing.ts`; utils `kebab-case.ts`; feature folders lowercase
- Prettier + Tailwind class sorting; ESLint must pass

### SSR-safe code (mandatory)
- No `window`, `document`, `localStorage` at module scope or during render — only in `useEffect`/event handlers
- No `Date.now()`, `Math.random()` or browser-only values in render output; format dates in a fixed timezone
- Check SSR compatibility before adding a library; lazy-load browser-only ones

### Performance
- Storefront bundle never includes admin code; pages are code-split
- `storefront/components/ui/Image`: explicit width/height, `srcset`, WebP, lazy by default; `priority` for LCP images
- Home hero first slide is static (no slider JS blocking LCP): plain `<img srcset sizes="100vw" width height loading="eager" fetchpriority="high">` in the main bundle (no SSR, so the bundle must run) plus a `<link rel="preload" as="image" imagesrcset ...>` in `storefront.blade.php` for the first active slider
- Hero slider JS: Swiper (+ CSS) is a lazy chunk requested after first paint and only when there are 2+ slides; only the next slide's image is loaded (others load when they become next); autoplay starts only after the next image has loaded
- Never cache full HTML pages. Cache data (plain arrays) per domain under its own key; each service clears only its own key on write; `HomeService` composes Home data from those caches
- Max 5 active sliders (validated in `SaveSliderRequest`)
- Targets: LCP < 2.5s, CLS < 0.1, mobile Lighthouse 90+

### Image presets (`config/media.php`, all webp)
| Preset | Output | Fit | Upload rule |
|---|---|---|---|
| `category` | 300x300, q80 | contain (padded, transparent; never cropped) | square logo, 300–1000px, max 512KB |
| `slider` | 1920x800, q72 + `-sm` variant 960x400, q70 (`uuid.webp` + `uuid-sm.webp`) | cover | at least 1920x800, up to 5000px, max 2MB |
| `product` (later) | 1200 + 400 thumb | — | — |

`FileUploadService::upload()` writes every size of a preset in one call, `delete()` removes all sizes, `srcset()` builds `960w, 1920w`. Uploaded files are served from `public/uploads` with a 1-year immutable `Cache-Control` (`public/uploads/.htaccess`).

## Design Direction
- Clean, modern, minimal: generous whitespace, neutral palette with one accent color, consistent spacing scale, rounded corners, subtle shadows
- Mobile-first (most traffic comes from Facebook on phones); sticky Order Now / Add to Cart bar on mobile product pages
- Clear price, stock and delivery info above the fold; large tap targets; fast, obvious order form
- Use design tokens (Tailwind theme variables) — no one-off colors or spacing

## Code Quality
- Clean Code, PSR-12 (PHP), ESLint + Prettier (TS)
- No duplication, no dead/commented-out code, no magic values (Enums/constants), max 2–3 nesting levels
- Full type hints and return types
- Comments only for non-obvious logic
- Dependency injection for testability

## Database
- No N+1: eager load relations used in loops/Resources
- `select()` only needed columns
- Index every FK and every column used in WHERE / ORDER BY / JOIN
- `chunk()` / `lazy()` / `cursor()` for large datasets
- Multi-step writes in `DB::transaction()`
- No observers/events with hidden side effects on hot paths (cart, order placement)
- Cache read-heavy data (catalog, categories, menu, delivery zones); invalidate explicitly on write
- Product search: MySQL FULLTEXT index for now

## Security
- All input validated via FormRequest classes
- `$fillable` only — never `$guarded = []`
- Authorization via Policies/Gates only
- Query builder/Eloquent bindings only — no raw SQL concatenation
- Secrets via `.env` + `config()` only
- All file uploads go through App\Services\Media\FileUploadService (with a preset for images). Never store files directly; delete old files only after the DB transaction commits.
- Rate limit auth, cart, order and search endpoints

## Performance Targets (backend)
- p50 < 200ms, p95 < 500ms, p99 < 1s
- Endpoints exceeding these must be optimized (cache, index, query refactor) before merge

## Testing
- Read testcase.md before writing any test and follow it.

## Build Order (current scope)
1. Admin auth (DONE)
2. Category admin (DONE)
3. Slider admin (DONE)
4. Product admin (DONE)
5. Settings (store info, delivery charge)
6. Landing page
7. Product details (Order Now)
8. Product search
9. Contact inbox
Do not start anything beyond the current step without my instruction.

## Don't Hallucinate
- Only what is marked DONE below exists. Verify before assuming any file, field, relation or route
- If a table structure or field isn't confirmed, ask first — don't invent it
- Don't infer feature scope from requirement docs; I specify scope before each feature

## Current Codebase State (Facts only)
- Laravel 13 + Inertia/React scaffolded: DONE
- TailAdmin admin panel in `resources/js/admin`: DONE (not yet feature-based)
- Legacy company-site code is removed (blog, portfolio, services, team, clients, career, about CMS, newsletter, quote requests, settings, the whole `/api` resource layer, `RejectXss`/`ValidateMimeType`, `resources/js/frontend`, TailAdmin demo pages and unused demo images). `routes/api.php` is empty on purpose (non-page JSON endpoints only)
- `shared/` has no UI; storefront primitives were moved to `storefront/components/ui`. Existing storefront code still uses the old per-component folders + `index.ts` barrels — not yet migrated to the flat structure
- Admin auth: DONE (details in doc/authentication.md)
- Dashboard (`Admin/Dashboard/Ecommerce`): widgets `EcommerceMetrics` and `RecentOrders` show TailAdmin demo data — to be replaced with real data after orders exist. Demo pages (Badges, Buttons, Basic Tables) were removed
- Category admin: DONE — Inertia only (no JSON API), two levels (parent → sub-category). `Admin\CategoryController` → `SaveCategoryRequest`/`IndexListRequest` → `Services\Category\CategoryService` → `Admin\Category\CategoryResource`; shared `ActiveStatus` enum; `parent_id` (nullable FK, restrict on delete): a parent must be an active top-level category, cannot be the category itself, and a category that has sub-categories cannot become one; a category with sub-categories cannot be deleted (validation error). Admin list is in tree order (sub-categories indented under their parent, `parent_name`/`children_count` in the resource, `parents` prop for the form select). Slug has a DB unique index (soft-deleted rows keep their slug). Active storefront categories are cached as plain arrays under `categories.active` in the shape `{slug,label,headline,description,image,children:[...]}` (active roots with their active children; children of an inactive root are hidden), cleared on every write and read by `HandleInertiaRequests`/`HomeService`. Storefront `useCategories()` returns the top-level nodes, `useCategory(slug)` also finds sub-categories. `CategorySeeder` seeds Kitchenware (Cookware, Kitchen Storage, Glassware) and Fashion (Women's Clothing, Bags & Accessories). UI in the flat folder `resources/js/admin/features/categories` (thin page `Admin/Categories/Index`); tests in `tests/Feature/Admin/CategoryTest.php`, `CategoryHierarchyTest.php`, `tests/Unit/Models/CategoryTest.php`, `tests/Unit/Seeders/CategorySeederTest.php`
- Media uploads: `App\Services\Media\FileUploadService` + `ImageResizeService` (intervention/image), presets (with size variants) and `MEDIA_RESIZE_ENABLED` in `config/media.php`, files on the `uploads` disk (`public/uploads`, no `storage:link`)
- `HasAuditFields` trait (created_by/updated_by/deleted_by) is on Category and Slider, both of which have those columns
- Database (`checkit_db`): users, categories, sliders, contact_messages, plus framework tables (sessions, cache, jobs, password_reset_tokens). `contact_messages` has a table but no model/UI yet (Build Order 9). Product tables: products, product_images, variant_heads, variant_values, product_variants, variant_selections, stock_movements. Admin sidebar: Dashboard, Products, Sliders, Categories
- Slider admin: DONE — same Inertia pattern as categories: `Admin\SliderController` → `SaveSliderRequest` → `Services\Slider\SliderService` → `Admin\Slider\SliderResource` (`image_url` + `image_srcset`); image required on create (min 1920x800, max 2MB), stored as 1920x800 + 960x400; max 5 active sliders; `button_link` must be a `/path` or http(s) URL; UI in `resources/js/admin/features/sliders`; tests in `tests/Feature/Admin/SliderTest.php` and `SliderMediaTest.php`
- Home: `HomeController` → `Services\Home\HomeService` (sliders + categories from their own caches `sliders.active` / `categories.active`, plain arrays, each cleared by its own service). Hero: `features/home/HeroSlider.tsx` (static first slide, lazy `HeroSwiper.tsx`, `HeroSlide.tsx`), blade preload of the first slide image; sample slides show only while there are no active sliders
- `App\Enums\ActiveStatus` (`active|inactive`) is the shared status enum for categories and sliders (TS: `ActiveStatus`, `STATUS_ACTIVE/INACTIVE` in `shared/types/status.ts` and `shared/constants/status.ts`); `categories` and `sliders` keep their constraints/indexes in their own create-table migrations (no extra migrations, use `migrate:fresh`)
- Product admin: DONE (spec in `doc/product/`). Backend: `Admin\ProductController` → `SaveProductRequest`/`IndexProductRequest` → `Services\Product\{ProductService, StockService, SkuGenerator, ProductPayloadChecker, ProductCache}` → `Admin\Product\{ProductListResource, ProductFormResource}`; money is typed in taka and stored in minor units (`App\Support\Money`); variants are `variant_heads` → `variant_values` → `product_variants` (+ `variant_selections`), a simple product has one `default` variant; stock changes only through `StockService` (every change writes a `stock_movements` row); featured products for Home are cached as plain arrays under `products.featured` (cleared by every product or stock write) and returned by `HomeService` as `featuredProducts`. UI in `resources/js/admin/features/products` (4-tab `ProductForm`, variant heads/chips with `useVariantMatrix`, list with URL-synced filters; pages `Admin/Products/{Index,Create,Edit}`); `useListFilters` accepts extra list-specific params. Tests: `tests/Feature/Admin/Product*Test.php`, `tests/Unit/Services/{StockService,ProductPayloadChecker}Test.php`, `tests/Unit/Support/MoneyTest.php`
- Orders, customers, settings and the rest of the e-commerce domain: not created yet
