# TSK-018 MT-003 C006 — correct payment balance expectation

This is a one-line TEST-HARNESS corrective only. Do not mutate product source.

Observed and proven:
- C005 successfully made the transport abort observable by blocking service workers for this spec.
- At both 320px and 390px the E2E now passes the AUD-12 assertions for:
  - uncertainty alert;
  - preserved amount/date;
  - Reintentar cobro;
  - retry success;
  - exactly one Cobro entry;
  - payment history contains $10.00 advance and $15.00 collection.
- The only remaining failure at both widths is line 94 expecting Saldo pendiente $0.00 MXN.
- The order total is $50.00, advance is $10.00, retry collection is $15.00, therefore received is $25.00 and pending balance is exactly $25.00.
- The same test already asserts Pago parcial and a $25.00 value in Resumen del pedido, so expecting $0.00 is internally contradictory.

Required correction:
1. In e2e/order-operations.spec.ts change only the pending-balance expectation after the $15 collection from $0.00 MXN to $25.00 MXN.
2. Preserve all other assertions, especially uncertainty UI, same-data retry, exactly-one collection, delivery/cancellation, and serviceWorkers: 'block'.
3. Do not modify any product source or backend/domain/config files.
4. Run the complete MT-003 focused validation gate.
5. PASS only with zero path violations and every validation command green.