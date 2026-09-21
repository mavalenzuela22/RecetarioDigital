# TSK-017 MT-002 C002 — Fresh Asset Build Before Browser Regression

Bounded corrective after MT-002 C001.

## Classified C001 result

C001 achieved:
- path policy PASS;
- TypeScript noEmit PASS;
- stale port cleanup PASS;
- TodayProduction Pest PASS;
- git diff check PASS;
- existing 320px and 390px production browser flows PASS;
- only the new A -> B range regression failed.

The Playwright webServer serves the Laravel app using assets from `public/build`.
MT-002 changed `resources/js/Pages/Production/Index.tsx`, but C001 did not run a fresh Vite production build before Playwright.

Therefore the browser was exercising stale compiled assets containing the old duplicated `start = useForm({ from, to })` implementation. The observed A -> B failure is consistent with stale bundle execution and is not yet evidence that the source fix is insufficient.

## Corrective objective

Validate the existing MT-002 source change against freshly compiled assets.

## Required execution

1. Re-observe current `Production/Index.tsx` and confirm the single-source-of-truth change is still present.
2. Do not modify product source unless a browser failure persists after a fresh build.
3. Run:
   - `npx tsc --noEmit`
   - `npm run build`
4. Remove only stale Docker container(s) publishing host port 18080.
5. Run `e2e/today-production.spec.ts` under the mobile Playwright project.
6. Run TodayProduction Pest.
7. Run git diff check.

If the A -> B test fails after fresh assets are definitely built and served, investigate and correct only the focused MT-002 boundary.

## Scope

May mutate only if required by post-build evidence:
- resources/js/Pages/Production/Index.tsx
- e2e/today-production.spec.ts
- optionally e2e/production-range.spec.ts

Preserve all previously validated MT-001 dirty changes untouched.

## PASS gate

- path policy PASS;
- TypeScript PASS;
- Vite production build PASS;
- Playwright today-production spec PASS, including A -> B;
- TodayProduction Pest PASS;
- git diff check PASS.
