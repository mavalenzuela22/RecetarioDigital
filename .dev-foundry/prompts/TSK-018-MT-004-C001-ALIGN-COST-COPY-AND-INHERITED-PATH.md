# TSK-018 MT-004 C001 — align cost-copy assertion and inherited path policy

Bounded corrective after primary MT-004.

Observed:
- Path policy had one violation only: inherited dirty routes/web.php was blocked by contract.
- TypeScript PASS.
- Production build PASS.
- RecipeTest PASS: 10 tests / 92 assertions, including stale conflict recovery and no extra version.
- Playwright reached the dirty-draft cost warning at both 320 and 390:
  "Cambiaste el borrador. El costo mostrado corresponde solo a la versión guardada ... y no representa tus cambios sin guardar. El costo del borrador se calculará y confirmará al guardar."
- The E2E then failed only because it searched for the final sentence as an exact standalone text node, but that sentence is part of a larger paragraph. This is a test-locator mismatch, not missing product copy.

Required correction:
1. Do not change product logic or copy unless mechanically necessary.
2. In e2e/recipe-costing.spec.ts, change only the failing assertion so it verifies the semantic sentence with substring/non-exact matching or against the enclosing status paragraph.
3. Preserve all other MT-004 assertions, especially:
   - saved-version cost label;
   - dirty draft warning;
   - real two-tab stale conflict;
   - draft preservation;
   - explicit rebase;
   - v3 immutable save;
   - 320/390 overflow.
4. Path policy must recognize the full inherited dirty set including routes/web.php and report zero violations.
5. Re-run the full focused MT-004 gate.
6. No product source mutation is expected.

PASS only if all commands pass and path policy has zero violations.