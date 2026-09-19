# TSK-006 C001 — Normalized Purchase Cost Test Corrective

Operate as a bounded test-only corrective for TSK-006.

## Classified failure
Initial TSK-006 implementation completed and path policy passed.

The only failing Pest assertion is in `tests/Feature/OrderCaptureTest.php`.

For a purchase of 1 kg at MXN 42:
- normalized canonical quantity is 1000 g;
- normalized unit cost is 42,000 micros MXN per gram;
- therefore the existing expectation `42000000` is incorrectly scaled by 1000.

The order snapshot assertions in the same test are correct:
- attributable unit cost: 4,200,000 micros for a 10-piece yield;
- attributable line cost: 8,400,000 micros for quantity 2.

This is a test defect, not a product defect.

## Required mutation
Modify only:
- `tests/Feature/OrderCaptureTest.php`

Correct the erroneous normalized purchase unit-cost expectation to the actual authoritative value `42000`.

Do not modify product implementation, routes, migrations, UI, existing domain services, manifests, Docker or Playwright config.
Do not weaken order snapshot assertions.

## Validation
Run the complete TSK-006 validation matrix after the fix.
If another failure remains, preserve evidence and stop for classification.
