# PHP RFC: `for {} yield` comprehensions

- **Version:** 0.1
- **Date:** 2026-07-01
- **Author:** Edd Mann <the@eddmann.com>
- **Status:** Draft
- **Target:** PHP 8.5 (experimental fork)
- **Implementation:** [`feature.patch`](feature.patch) *(bundles the `Option`/`Result` types it iterates)*

## Introduction

Nested `map`/`flatMap`/`filter` pipelines are powerful but read poorly in PHP. This
RFC adds Scala-style comprehensions with a single syntax that covers **both** list
comprehensions over arrays (cartesian product + guards) **and** monadic do-notation
over `Option`/`Result` (short-circuiting bind).

## Proposal

```php
for { $x = [1,2,3]; $y = [10,20] } yield $x + $y;   // [11,21,12,22,13,23]
for { $x = range(1,9); if $x % 2 } yield $x * $x;   // odd squares
for { $x = Some(1); $y = Some(2) } yield $x + $y;    // Some(3)
for { $x = Ok(1);   $y = Err("e") } yield $x + $y;   // Err("e") — short-circuits
for { $u = $users } yield $u->id => $u;              // dict comprehension
```

- `=` is the **bind** operator inside `for {}` (contextual; no new token).
- `if` introduces a **guard**; later generators may depend on earlier bindings.
- `yield $k => $v` builds an associative array (dict form).
- The result **type follows the first generator**; the brace form `for {` selects a
  comprehension, the paren form `for (` remains the C-style loop.

### Implementation

Desugars (as in Scala) to nested `flatMap`/`map`/`filter`, but the calls target
internal dispatch helpers so one desugar serves both worlds: arrays get
list-comprehension semantics, objects delegate to their own `map`/`flatMap`/`filter`
(so `Option`/`Result` and any userland type with those methods work). Each callback
is a synthesised auto-capturing arrow function.

## Backward Incompatible Changes

None. `for {` is new syntax; `for (` is unchanged. Mixing an array with an
`Option`/`Result` in one comprehension is a runtime error (the first generator picks
the world).

## Proposed PHP Version(s)

Next PHP 8.x minor.

## RFC Impact

- **Opcache:** desugars to ordinary calls/closures; no new opcode.
- **Dependency:** requires the `Option`/`Result` types (feature 02), which this
  patch bundles.

## Future Scope

- Pattern-binding generators; parallel (`zip`) comprehensions.

## Proposed Voting Choices

Yes/No, 2/3 majority.

## Patches and Tests

- [`feature.patch`](feature.patch); [`tests/`](tests/): `comprehension`,
  `comprehension_dict` (arrays, guards, dependent generators, Option/Result
  short-circuit, dict folding).

## References

- Scala for-comprehensions: https://docs.scala-lang.org/
