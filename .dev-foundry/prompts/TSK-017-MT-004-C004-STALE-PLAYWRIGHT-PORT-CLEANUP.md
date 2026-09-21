# TSK-017 MT-004 C004 — stale Playwright port cleanup closure

Operate only as a bounded validation closure executor. Do not modify product code, tests, docs, governance files, or repository lifecycle state.

Context:
- MT-004 C002 functionally passed all focused validation commands.
- MT-004 C003 passed path policy with zero violations, TypeScript, Vite build, HomeTest, and git diff check.
- C003 failed only because Playwright reported http://127.0.0.1:18080/up already in use.
- Current branch/head must remain tsk-017-pilot-blocker-correctness-economic-trust @ bbabd266be69915391c54ddca5bc906d773bba57.

Required behavior:
1. Make zero repository mutations.
2. Do not create any .dev-foundry files and do not invoke Foundry Runner.
3. Preserve all existing dirty files byte-for-byte.
4. Run only the contract validation commands.
5. The validation sequence explicitly removes any stale Docker container publishing 18080 before Playwright.
6. Return PASS only if all validations pass and path policy has zero violations.

No implementation changes, no refactor, no scope expansion.