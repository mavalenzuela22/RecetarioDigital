# TSK-014 C003 — Bounded Transitive Security Overrides

Operate as the third bounded corrective for TSK-014.

## Classification

Two separate lockfile-only attempts proved that npm will not advance the vulnerable nested Babel/Browserslist sub-tree on its own.

Current blocking advisories:
- browserslist <= 4.28.6 — HIGH; patched in 4.28.7;
- baseline-browser-mapping >= 2.0.0 < 2.11.0 — MODERATE; patched in 2.11.0.

The current direct frontend toolchain is Vite 7.x + @vitejs/plugin-react 5.2.0.
Do NOT upgrade @vitejs/plugin-react to 6.x in this task because 6.x explicitly drops Vite 7 support and would force a broader Vite 8 toolchain migration.

All non-audit regression gates have repeatedly passed.

## Corrective boundary

Allow changes only to:
- package.json
- package-lock.json
- this corrective's governance artifacts.

Use npm `overrides` as a last-resort bounded security control, because lockfile-only remediation has been independently proven ineffective.

Required behavior:
1. Add the smallest possible override(s) necessary to ensure the installed tree resolves:
   - browserslist >= 4.28.7;
   - baseline-browser-mapping >= 2.11.0.
2. Prefer exact patched versions for determinism unless npm's dependency solver requires a compatible patch/minor newer version.
3. Do not change any direct dependency or devDependency version constraint.
4. Do not upgrade Vite or @vitejs/plugin-react.
5. Do not add packages.
6. Run `npm install --package-lock-only` after adding overrides.
7. Run `npm ci`.
8. Record `npm ls browserslist baseline-browser-mapping` evidence and verify no invalid dependency tree.
9. Require `npm audit --audit-level=moderate` exit 0.
10. Rerun the complete TSK-014 validation matrix.

A remaining low-severity advisory is acceptable for TSK-014 only if no moderate/high/critical advisories remain, consistent with the task completion rule.

If npm reports an override conflict or an invalid dependency tree, stop BLOCKED and do not expand to a Vite 8 migration.
