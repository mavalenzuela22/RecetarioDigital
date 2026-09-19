# TSK-002 — UX/UI Foundation with Astra

## Status
Prepared for operator execution in ChatGPT Desktop **Work** using **GPT-6 Astra**.

## Purpose
Establish the complete UX/UI and brand foundation for **EmprendimientoOS v1** before domain implementation continues.

TSK-002 is intentionally design-first. It must convert the current product definition, architecture baseline, UX principles, and TSK-001 application shell into a coherent, implementation-ready visual system and reference experience.

This task exists to prevent later implementation agents from guessing what the design intent was.

## Product context
EmprendimientoOS is a mobile-first PWA for a small food entrepreneur who runs day-to-day costing, recipes, products, orders, production, collections, and historical economics primarily from a phone.

The desired emotional character is:
- warm
- capable
- practical
- handcrafted
- food-oriented
- entrepreneurial
- confident
- resilient
- modern without feeling corporate
- feminine without becoming childish, sugary, stereotyped, or patronizing

The intended energy is that of a resourceful household entrepreneur who handles cooking, customers, money, logistics, family life, and practical problems with confidence.

Do not translate that archetype literally into clichés, caricatures, debt jokes, pink stereotypes, or decorative "girl boss" tropes. Translate it into clarity, warmth, confidence, speed, ownership, and visual character.

## Language authority
**EmprendimientoOS v1 is Spanish-first.**

All user-visible design evidence, high-fidelity screens, component examples, navigation, labels, messages, empty states, errors, confirmations, onboarding text, and microcopy must be authored in natural production-quality Spanish.

Initial locale target: **es-MX**.

The architecture should remain compatible with future localization, but additional languages are outside TSK-002 and outside v1 unless separately authorized.

English placeholder UI is not acceptable as final design evidence.

## Authoritative inputs
Astra must read and treat these as authoritative:
- `docs/PRODUCT-DEFINITION-v1.md`
- `docs/ARCHITECTURE-v1.md`
- `docs/UX-PRINCIPLES-v1.md`
- the current TSK-001 application shell

If those sources conflict with this task, surface the conflict explicitly instead of silently inventing a resolution.

## Execution mode
This task is operator-executed in ChatGPT Desktop **Work** with GPT-6 Astra.

Astra may inspect the repository and current shell.

Astra must not modify production application code during TSK-002.

Permitted output boundary:
- `docs/design/**`

Read-only outside that boundary.

## Required workflow

### Phase 1 — Product and shell audit
Inspect the authoritative product documents and current application shell.

Document:
- current UX strengths
- current UX weaknesses
- mobile interaction risks
- information hierarchy risks
- accessibility risks
- places where the shell visually conflicts with the desired product character

Do not implement fixes yet.

### Phase 2 — Three visual/interaction directions
Produce three materially distinct directions, each with enough visual evidence to make a real decision.

Required concepts to explore:

1. **Cocina cálida artesanal**
   - welcoming
   - tactile
   - appetizing
   - handmade
   - calm

2. **Emprendedora fuerte**
   - clean
   - decisive
   - modern
   - highly legible
   - operationally powerful

3. **Mercadito premium de barrio**
   - local
   - approachable
   - flavorful
   - distinctive
   - polished without becoming luxury-corporate

For each direction include:
- color palette
- typography direction
- surface/card treatment
- icon style
- image/illustration direction
- sample navigation
- a high-fidelity Today/Home concept
- one secondary workflow sample
- explanation of tradeoffs

Then **STOP and ask the operator to select, reject, or combine directions**.

Do not produce the final brandbook before operator selection.

### Phase 3 — Selected direction refinement
After operator selection, refine the chosen direction until explicitly accepted.

Iterate based on operator feedback.

Do not treat the first selected draft as final merely because one direction was chosen.

### Phase 4 — Complete brandbook and design system
After visual direction acceptance, produce a complete implementation-ready brandbook and design system.

No implementation-critical design decision may exist only as prose.

Every appearance or interaction rule must be represented by one or more of:
- exact token
- asset
- component specification
- quantitative rule
- interaction/state specification
- unambiguous visual reference

### Phase 5 — Core journey design
Produce high-fidelity, Spanish-first mobile reference screens for at least:

- Hoy / Inicio
- registrar compra de ingrediente
- crear o editar receta y rendimiento
- producto / costo / escenarios de precio
- registrar pedido
- pago, saldo y entrega
- vista de producción
- comparación histórica / "as of"

Include meaningful empty, loading, error, validation, disabled, success, and destructive-confirmation states where relevant.

### Phase 6 — Handoff package
Produce enough design authority that a fresh implementation agent can build a new screen consistent with EmprendimientoOS without asking Astra what the design meant.

The package must be self-contained under `docs/design/**`.

## Required design package
The final design package must contain, at minimum:

- brandbook
- brand personality and voice
- logo/wordmark treatment if used
- exact color system and semantic color roles
- typography system
- spacing scale
- border/radius system
- shadows/elevation
- responsive rules and breakpoints
- motion principles and timings where relevant
- accessibility/contrast rules
- iconography specification
- actual reference icon assets or a clearly named source set plus exact usage rules
- image/illustration art direction
- app icon source and export guidance
- PWA icon references, including maskable treatment
- splash-screen composition and export guidance
- background/decorative assets used by the visual system
- component library
- component states
- navigation patterns
- forms and numeric-entry patterns
- cards, chips, badges, tabs, bottom sheets, dialogs, toasts, empty states, loading states, errors, confirmations
- monetary-value treatment
- order and payment status treatment
- high-fidelity core screens
- responsive/mobile behavior
- Spanish microcopy/tone guide
- implementation mapping for React + Tailwind
- design tokens in a machine-readable form where practical
- asset manifest with filenames, purpose, format, and intended repository location
- Do/Don't examples
- handoff completeness checklist

## Suggested repository structure
Astra may refine filenames, but the conceptual structure should remain clear:

```text
docs/design/
  README.md
  brandbook/
  tokens/
  components/
  screens/
  icons/
  assets/
  imagery/
  splashes/
  implementation-guide/
  prototypes/
```

## Mobile UX rules
The design must honor the existing mobile-first principles and additionally ensure:
- common actions are comfortable with one hand where practical
- touch targets are appropriate for phone use
- no workflow depends on hover
- no enterprise-style horizontal tables for routine work
- important money and operational states are immediately scannable
- forms use Spanish copy at realistic lengths
- numeric flows consider phone numeric keyboards
- the interface remains useful while cooking, packaging, shopping, or coordinating deliveries
- visual decoration never obscures cost, money, quantity, order, production, or collection information

## Brand character guardrails
The interface may use culinary cues such as warm food-inspired colors, subtle recipe-paper/label motifs, tasteful ingredient imagery, kitchen-related iconography, and handcrafted visual details.

Avoid:
- generic enterprise dashboard styling
- excessive pink/feminine cliché
- infantilization
- scrapbook overload
- fake rustic clutter
- low-contrast beige-on-beige aesthetics
- gratuitous chef-hat/cupcake icon spam
- cultural caricature
- visual noise that reduces operational clarity

## Core UX questions the design must answer
A user opening the app should quickly understand:
- ¿Qué tengo que preparar hoy?
- ¿Qué tengo que entregar?
- ¿Quién me debe?
- ¿Cuánto espero cobrar?
- ¿Cuánto me cuesta hacer esto?
- ¿Cuánto gano por pieza o pedido?
- ¿Subieron mis costos?
- ¿Qué precio me conviene usar?

## Acceptance criteria
TSK-002 is not PASS until all of the following are true:

1. The operator explicitly accepts a visual direction.
2. The final brandbook and design system exist under `docs/design/**`.
3. High-fidelity Spanish-first references exist for the core journeys.
4. Exact tokens and component/state specifications exist.
5. Required visual assets are delivered or have explicit reproducible source/export instructions.
6. App icon, PWA icon treatment, and splash direction are defined.
7. Spanish microcopy is production-quality and reviewed as part of layout.
8. The system is demonstrably mobile-first.
9. The design is implementable in the existing Laravel + Inertia + React + TypeScript + Tailwind architecture without requiring a new frontend framework.
10. A fresh implementation agent can understand how to implement a new conforming screen using only the design package and authoritative product docs.
11. No production application code was modified as part of TSK-002 unless the operator separately authorizes an implementation corrective.

## Explicit non-goals
TSK-002 does not implement:
- Ingredients domain logic
- Recipe domain logic
- Product costing
- Orders
- Production logic
- Analytics
- database schema
- production deployment

It defines the experience those later tasks must implement.

## Completion evidence
The final TSK-002 handoff should record:
- selected direction
- rejected/merged direction notes
- final design package paths
- final asset manifest
- unresolved design decisions, if any
- operator acceptance
- handoff readiness result

