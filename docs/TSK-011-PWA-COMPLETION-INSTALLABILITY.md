# TSK-011 — PWA Completion & Installability

## Objective

Complete the EmprendimientoOS v1 installable PWA shell without introducing offline business-data semantics.

The application is already mobile-first and has a basic web app manifest. This task closes the accepted PWA design debt by:
- publishing the already-approved app icons;
- completing the production manifest;
- registering a bounded service worker;
- proving the shell is installable-capable while keeping authenticated business data network-only.

## Authority

- `docs/ARCHITECTURE-v1.md` PWA section:
  - manifest;
  - app metadata;
  - suitable mobile icons;
  - installable standalone presentation;
  - complex offline synchronization explicitly deferred.
- `docs/design/implementation-guide/manifest.example.json`
- `docs/design/assets/brand/app-icon-180.png`
- `docs/design/assets/brand/app-icon-192.png`
- `docs/design/assets/brand/app-icon-512.png`
- `docs/design/assets/brand/maskable-512.png`
- `docs/design/assets/brand/favicon.ico`
- accepted design finding PWA-01.

Do not modify `docs/design/**`.

## Production assets

Copy the accepted design assets byte-for-byte to:
- `public/icons/app-icon-180.png`
- `public/icons/app-icon-192.png`
- `public/icons/app-icon-512.png`
- `public/icons/maskable-512.png`
- `public/favicon.ico`

Do not regenerate, redraw or recolor them.

## Manifest

Update `public/manifest.webmanifest` to preserve the current product identity and provide:
- `name`: EmprendimientoOS
- `short_name`: EmprendimientoOS
- Spanish/Mexico locale
- root start_url and scope
- standalone display
- portrait-primary orientation
- accepted design background/theme color `#FFF8ED`
- icons:
  - 192x192 any
  - 512x512 any
  - 512x512 maskable

Do not add speculative shortcuts, screenshots, share targets, protocol handlers or categories.

## HTML metadata

Update the Laravel/Inertia root view only as needed to:
- keep the manifest link;
- align theme/background metadata with the accepted manifest;
- expose favicon;
- expose Apple touch icon using the approved 180px asset.

Do not duplicate app content in Blade.

## Service worker

Create `public/sw.js`.

The service worker is deliberately bounded.

Allowed caching:
- `/manifest.webmanifest`
- `/favicon.ico`
- `/icons/*`
- immutable/versioned Vite assets under `/build/*`

Required behavior:
- precache manifest and app icons during install;
- runtime cache the approved static paths above;
- use cache-first for approved static assets with network population;
- clean prior EmprendimientoOS static-cache versions on activate;
- call `skipWaiting()` and `clients.claim()` so deploys converge without requiring an old worker to remain indefinitely.

Strictly prohibited from caching:
- navigation/document requests;
- `/login` HTML or auth responses;
- business routes;
- Inertia responses;
- POST/PUT/PATCH/DELETE;
- recipe private-image route;
- any request carrying application business data.

There is no offline business UI, mutation queue, background sync or stale business-data fallback in v1.

## Registration

Register `/sw.js` from the existing frontend bootstrap in `resources/js/app.tsx`.

Requirements:
- only when `serviceWorker` is supported;
- register after page load;
- scope root;
- registration failure must not prevent application startup;
- no user-visible install prompt is required.

## Backend/static verification

Add focused automated coverage that proves:
1. manifest is public and valid JSON;
2. manifest identity/start_url/scope/display/lang/colors are exact;
3. icon entries are exact;
4. every declared icon exists with non-empty bytes;
5. favicon and Apple icon source exist;
6. `sw.js` is publicly retrievable;
7. SW source does not contain business route paths as precache entries;
8. SW source explicitly limits cache handling to the approved static namespaces.

Do not test browser install banners; those are browser/platform policy, not application semantics.

## Playwright PWA coverage

Add `e2e/pwa.spec.ts`.

At mobile width:
1. open public login page;
2. verify manifest link resolves;
3. fetch manifest and verify 192/512/maskable entries;
4. verify favicon / Apple touch icon links;
5. wait for service-worker registration and assert its active/installed registration uses `/sw.js`;
6. authenticate through the real login helper;
7. navigate among representative business pages and verify normal authenticated behavior remains online/network-backed;
8. inspect Cache Storage and assert no cached request URL contains:
   - `/login`
   - `/ingredientes`
   - `/recetas/` business pages/private image endpoints
   - `/productos`
   - `/pedidos`
   - `/produccion`
9. assert cached URLs, if any, are limited to manifest/favicon/icons/build.

The existing full Playwright suite must remain green.

## Required validation matrix

1. `docker compose build app`
2. `docker compose run --rm app composer install --no-interaction --prefer-dist`
3. `npm ci`
4. `docker compose run --rm app php artisan test`
5. `npx tsc --noEmit`
6. `npm run build`
7. cleanup known E2E container
8. `npx playwright test --project=mobile`
9. `git diff --check`

## Guardrails

Target <=18 changed paths; hard maximum 24.

No dependencies.
No migrations.
No domain/service changes.
No auth changes.
No changes to `docs/design/**`.

## Non-goals

- offline order/purchase/payment entry
- background sync
- push notifications
- install promotion UI
- native wrapper
- app store packaging
- caching authenticated HTML/Inertia/business payloads
- custom splash-image guarantees

## Completion

TSK-011 closes only when:
- approved icons are shipped;
- manifest is complete;
- service worker is registered and bounded to static shell assets;
- no business/auth data is cached;
- full backend/build/E2E matrix passes;
- governed promotion/reconciliation/cleanup completes.
