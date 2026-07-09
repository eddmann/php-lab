# `for {} yield` comprehensions (arrays + `Option` / `Result`)

> **Inspiration:** Scala `for`-comprehensions · **Layer:** parser + compiler + runtime helpers · **Commit:** `a85ca7e` · **Builds on:** [feature 02 — `Option`/`Result` + combinators](../02-option-result/) (bundled)

## What it is

Scala-style comprehensions, **unified**: the *same* syntax does a list comprehension
over arrays (cartesian product + guards) **and** monadic do-notation over
`Option` / `Result` (short-circuiting bind).

```php
for { $x = [1,2,3]; $y = [10,20] } yield $x + $y;   // [11,21,12,22,13,23]
for { $x = range(1,9); if $x % 2 } yield $x * $x;   // odd squares
for { $x = Some(1); $y = Some(2) } yield $x + $y;    // Some(3)
for { $x = Ok(1);   $y = Err("e") } yield $x + $y;   // Err("e")  (short-circuits)
```

- `=` is the **bind** operator inside `for {}` (contextual — no new token).
- `if` introduces a **guard**.
- A later generator can depend on an earlier binding
  (`$y = range(1, $x)`).

## Why

Comprehensions are the readable face of nested `flatMap`/`map`/`filter`. Unifying
arrays and option/result under one syntax means the same mental model covers both
"iterate over collections" and "thread success through several fallible steps".

## How it works

It desugars (exactly like Scala) to nested `flatMap` / `map` / `filter`, but the
calls target **internal dispatch helpers** — `__fc_map`, `__fc_flatMap`,
`__fc_filter` (in `Zend/zend_option_result.c`) — so one desugar serves both worlds:

- an **array** gets list-comprehension semantics (reindexed map/filter, and a
  flatten-one-level for the cartesian product);
- an **object** delegates to its own `map` / `flatMap` / `filter` method — so it
  works for `Option` / `Result` *and* any userland type that exposes those methods.

Each callback is a synthesized **auto-capturing arrow function**, so later
generators and guards see the earlier bindings.

`for {` versus `for (` selects the comprehension: the brace form is the
comprehension, the paren form is the unchanged C-style loop. The result **type**
follows the **first** generator.

## Dict and set comprehensions

A **key/value** yield builds an associative array — a dict comprehension:

```php
for { $u = $users } yield $u->id => $u;      // [id => user, ...]
for { $x = [1,2,3,4]; if $x % 2 === 0 } yield $x => $x*$x;   // [2=>4, 4=>16]
```

The `yield $k => $v` form makes the comprehension body a `[key, value]` pair; the
flat list of pairs is folded into an associative array by an internal
`__fc_pairs_to_map` step (later keys win). Dict comprehensions are for array
generators.

**Set comprehensions need no new syntax**: because the desugar delegates to the
source's own `map`/`filter`/`flatMap`, iterating a [`Set`](../07-collections/) source
returns a `Set`:

```php
for { $x = Set::of(1,2,3,4) } yield $x * 2;   // Set(2, 4, 6, 8)
```

(`Set` comes from [feature 07 — persistent collections](../07-collections/); it is
not bundled by this feature, so this example needs that build too.)

## Caveats

- Mixing an array with an `Option` in one comprehension is a **runtime error**
  (the first generator picks the world; the rest must agree).
- The `for {` / `for (` distinction is purely the next token after `for`.

## Tests

- `smoke.sh` — smoke tests
- `tests/comprehension.phpt`
