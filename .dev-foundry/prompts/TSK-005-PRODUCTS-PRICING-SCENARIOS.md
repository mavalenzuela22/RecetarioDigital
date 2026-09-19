# TSK-005 — Products, Cost Allocation & Pricing Scenarios — Implementation Handoff

Operate as the bounded implementation executor for RecetarioDigital / EmprendimientoOS.

Read and obey:
1. `.dev-foundry/profiles/project-operating-profile-v2.yaml`
2. `docs/TSK-005-PRODUCTS-PRICING-SCENARIOS.md`
3. product authorities referenced by that task
4. existing TSK-003 ingredient purchase economics
5. existing TSK-004 recipe/version/current-cost implementation
6. accepted product flow under `docs/design/**` as read-only design authority.

## Implement
Build the smallest safe complete product/pricing boundary described by TSK-005:
- stable product from existing recipe, one product per recipe in v1;
- piece sale unit;
- immutable versioned additional-cost profiles;
- exact batch/unit/order allocations with explicit reference order quantity;
- current cost derived from current recipe economics;
- server-authoritative ×2/×2.5/×3/×3.5 scenario math;
- append-only idempotent price history;
- active/inactive state;
- Spanish mobile-first list/configuration/detail flow;
- real Pest and Playwright coverage.

## Critical invariants
- Do not use binary floats for authoritative money or percentage math.
- Do not silently use 0 for incomplete recipe/product cost.
- Do not overwrite old cost profiles or price rows.
- Do not count order/delivery cost twice.
- Distinguish multiplier from margin.
- Scenario exploration does not persist.
- Price change requires explicit confirmation and server idempotency.
- A later ingredient/recipe cost change may change current product cost but never rewrite stored profile/price history.
- Do not implement Orders.
- Do not modify `docs/design/**`.
- Do not modify dependency manifests, Docker files or Playwright config.
- Do not weaken TSK-003 or TSK-004 behavior/tests.

Use existing exact-decimal patterns where safe, but do not introduce broad refactors merely to share helpers.

## Failure discipline
Run the contract validation commands. Classify any failure before mutation. Correct only genuine bounded implementation/test/harness defects. No blind retry.

If the complete visible changed-path set would exceed 30, stop BLOCKED and report the path pressure.
