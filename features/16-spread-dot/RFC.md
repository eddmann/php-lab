# PHP RFC: Spread-dot operator (`*->`)

- **Version:** 0.1
- **Date:** 2026-07-09
- **Author:** Edd Mann <the@eddmann.com>
- **Status:** Draft
- **Target:** PHP 8.5 (experimental fork)
- **Implementation:** [`feature.patch`](feature.patch)

## Introduction

Projecting a method or property across a collection is one of the most common
things code does, yet it always spells out `array_map(fn($x) => $x->name, $xs)` or
a `foreach`. Borrowing Groovy's spread-dot operator, this RFC adds `*->` so the
projection reads as a single, direct expression.

## Proposal

`$coll*->member(args)` calls `member` on every element and collects the results;
`$coll*->prop` reads a property from every element:

```php
$users*->getName();          // array of names
$users*->name;               // array of names (property form)
$orders*->total(true);       // arguments are forwarded to each call
$boxes*->inc()*->get();      // chains: each stage maps and returns an array
```

Keys are preserved, elements are visited left to right, and the left operand may
be any expression.

## Backward Incompatible Changes

None. `*->` is a brand-new token, recognised only when `*` is immediately followed
by `->` — a sequence that is a syntax error in stock PHP. Multiplication (`*`),
exponentiation (`**`) and multiply-assign (`*=`) are unaffected.

## Semantics / Scope (v1)

`$coll*->m(args)` desugars to:

```php
array_map(fn($__each) => $__each->m(args), $coll)
```

and the property form to `array_map(fn($__each) => $__each->prop, $coll)`.
Consequences:

- The operand must be an **array**; a non-array yields `array_map()`'s `TypeError`.
  `Traversable`s should be materialised with `iterator_to_array(...)` first. A
  future revision could lower to a helper that also accepts iterables.
- Arguments live inside the mapped closure, so they are evaluated **once per
  element**.
- No null-safe variant (`*?->`) in v1.

## RFC Impact

- **Opcache:** none. The desugar emits an ordinary `array_map` call and arrow
  function; there is no new opcode.
- **Tokenizer:** one new token, `T_SPREAD_ARROW` (`*->`), exposed to
  `token_get_all()`/`token_name()`.
- **Grammar:** two productions mirroring the existing `->` method-call and
  property-access rules; the grammar remains conflict-free (`%expect 0`).

## Implementation

1. **Scanner:** `*->` in `ST_IN_SCRIPTING` emits `T_SPREAD_ARROW` and enters the
   look-for-property state, exactly like `->`.
2. **Parser:** `array_object_dereferenceable T_SPREAD_ARROW property_name [argument_list]`
   builds `array_map(fn($__each) => $__each->member(...), $coll)`. Because the
   result is a call expression, it is itself dereferenceable, which is what lets
   `*->` chain. `array_map` is emitted fully-qualified so it resolves globally.

## Proposed PHP Version(s)

Next PHP 8.x minor.

## Proposed Voting Choices

Yes/No, 2/3 majority.

## Patches and Tests

- [`feature.patch`](feature.patch); [`tests/`](tests/): method and property
  spread, arguments, key preservation, chaining, empty collections, namespaced
  call sites, parenthesised operands, evaluation order, and confirmation that
  `*`/`**`/`*=` are unaffected.

## References

- Groovy spread-dot operator: https://groovy-lang.org/operators.html#_spread_operator
