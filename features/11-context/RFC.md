# PHP RFC: Context parameters (`context` / `provide`)

- **Version:** 0.1
- **Date:** 2026-07-01
- **Author:** Edd Mann <the@eddmann.com>
- **Status:** Draft
- **Target:** PHP 8.5 (experimental fork)
- **Implementation:** [`feature.patch`](feature.patch) *(bundles `defer`)*

## Introduction

Cross-cutting values — a clock, a logger, a DB handle, the current request — usually
have to be threaded through every function signature by hand, even functions that only
pass them along. Borrowing from Scala's `given`/`using`, this RFC lets a value be
**provided once** and flow implicitly down the call chain to any function that declares
it as a `context` parameter, resolved by type.

## Proposal

```php
class SystemClock { function time(): int { return time(); } }

function now(context Clock $c): int { return $c->time(); }

provide new SystemClock() {     // active for everything called inside the block
    echo now();                 // $c is auto-supplied — no explicit argument
}
```

- A `context` parameter is never passed by the caller; it is resolved from the
  nearest enclosing `provide` whose value is `instanceof` the declared type (so an
  interface or base-class parameter is satisfied by any subtype).
- **Block form** `provide V { … }` is active for the dynamic extent of the block;
  **statement form** `provide V;` for the rest of the enclosing function.
- **Nearest wins**: a nested `provide` of the same type shadows an outer one and is
  restored on exit. A missing value is an error unless the parameter has a default.
  A `provide` that throws still pops its value.

### Implementation

A compile-time desugar plus a small per-request stack. A pre-pass removes each
`context T $c` from the signature and prepends `$c = \__ctx_resolve("T");` to the body
(with a `\__ctx_has` guard when a default is present), so the public signature stays
ordinary. `provide V { B }` lowers to `\__ctx_push(V); try { B } finally {
\__ctx_pop(); }`; the statement form reuses **`defer`** for the scoped pop (hence the
bundled dependency). The runtime stack lives in `zend_context.c` and matches with
`instanceof_function`, newest-first.

## Backward Incompatible Changes

- `context` and `provide` are **case-sensitive, lowercase-only keywords** — so the
  common `class Context {}` still parses; a lowercase `class context {}` does not.
  They are semi-reserved, so `$o->context(...)` / `$o->provide()` remain valid.

## Proposed PHP Version(s)

Next PHP 8.x minor.

## RFC Impact

- **Reflection:** context parameters are removed from the public signature, so
  reflection sees a normal, context-free function.
- **Scope (v1):** context values must be objects (matched by class/interface);
  named functions, methods, and closures (not arrow functions); auto-only (no
  per-call override).
- **Dependency:** the `provide` statement form lowers to `defer` (feature 04), which
  this patch bundles.

## Future Scope

- Scalar context values via a tag type; explicit per-call override.

## Proposed Voting Choices

Yes/No, 2/3 majority.

## Patches and Tests

- [`feature.patch`](feature.patch); [`tests/`](tests/): `context` — resolution by
  type, block and statement `provide`, nested shadowing, interface/base-class match,
  missing-value error, default value, and pop-on-throw.

## References

- Scala `given`/`using`; Kotlin coroutine context; React context.
