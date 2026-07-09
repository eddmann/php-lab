# PHP RFC: `with` expressions (clone-with sugar)

- **Version:** 0.1
- **Date:** 2026-07-01
- **Author:** Edd Mann <the@eddmann.com>
- **Status:** Draft
- **Target:** PHP 8.5 (experimental fork)
- **Implementation:** [`feature.patch`](feature.patch)

## Introduction

PHP 8.5 added the "clone with" capability — `clone($obj, ['k' => $v])` — but the
spelling reads like data plumbing. Borrowing from C#/Scala/Rust record updates, this
RFC adds a `with` expression as readable surface syntax over that capability.

## Proposal

```php
$older = $person with { age: $person->age + 1, status: 'active' };
$copy  = $cfg with { ...$overrides, debug: true };   // spread; later wins
$chain = $p with { a: 1 } with { b: 2 };             // chainable
```

- `EXPR with { name: value, ... }` — bare-identifier keys, expression values.
- `...$array` spreads a dynamic set of overrides; trailing comma allowed.
- The left operand is any expression (`$this with {…}`, `(new P) with {…}`,
  `f() with {…}`).

### Implementation

Pure parser sugar — no AST node, compiler, runtime, or C changes. `EXPR with { k: v,
...$s }` lowers to `clone(EXPR, ['k' => v, ...$s])`, the official 8.5 clone-with
call. It therefore inherits all correct behaviour: it calls `__clone()`, and
respects visibility, property hooks, and `readonly` — byte-for-byte identical to the
equivalent `clone()`.

## Backward Incompatible Changes

- `with` is **semi-reserved** — still usable as a method (`$o->with()`), static
  method, and constant. A global `function with()` or class named `with` would no
  longer parse.
- `readonly` overrides follow native clone-with rules (writable only from a scope
  where the property is writable).

## Proposed PHP Version(s)

Next PHP 8.x minor.

## RFC Impact

- **Opcache:** compiles to the existing `clone()` call AST; no new opcode.

## Future Scope

- Positional record-update for `readonly`-heavy value objects.

## Proposed Voting Choices

Yes/No, 2/3 majority.

## Patches and Tests

- [`feature.patch`](feature.patch); [`tests/`](tests/): named fields, spread,
  chaining, `readonly`, `__clone`, visibility, and expression operands.

## References

- C# `with` expressions; Scala `copy()`; Rust struct-update `..`.
