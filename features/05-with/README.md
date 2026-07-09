# `with` expressions — immutable clone-with sugar

> **Inspiration:** C# `with`, Scala `copy()`, Rust struct-update `..` · **Layer:** parser only · **Builds on:** PHP 8.5 clone-with

## What it is

A readable record-update surface over PHP 8.5's new "clone with" capability. Copy an
object, overriding some properties, without the array-and-quoted-keys plumbing:

```php
$older = $person with { age: $person->age + 1, status: 'active' };
$copy  = $cfg with { ...$overrides, debug: true };   // spread supported
```

Compare the native 8.5 form:

```php
$older = clone($person, ['age' => $person->age + 1, 'status' => 'active']);
```

## Syntax

- `EXPR with { name: value, ... }` — bare-identifier keys, expression values.
- `...$array` members spread a dynamic set of overrides; later members win.
- Trailing comma allowed.
- Chains: `$p with { a: 1 } with { b: 2 }`.
- The left operand is any expression: `$this with {…}`, `(new P) with {…}`,
  `f() with {…}`.

## Why

PHP 8.5 *added the capability* (clone-with) but the spelling reads like data
plumbing. The whole repo's premise is nicer syntax over PHP primitives, and the
capability now existing is exactly what makes this clean. `with { }` matches how
C#/Scala/Rust spell record updates.

## How it works

**Pure parser sugar — no AST node, no compiler, no runtime, no C.** PHP 8.5's
`clone($obj, [...])` is itself parser sugar that lowers to a `ZEND_AST_CALL` of the
`clone` builtin. So `with` lowers to exactly that call:

```
EXPR with { k: v, ...$s }   ──►   clone(EXPR, ['k' => v, ...$s])
```

The grammar adds a `T_WITH` token and an `expr` rule that builds the `clone()` call
AST, plus member-list nonterminals that assemble a `ZEND_AST_ARRAY` (bare
identifiers become constant string keys, like named-argument syntax; `...$x` becomes
an `ZEND_AST_UNPACK` element). The `%expect 0` grammar-conflict gate is preserved by
mirroring `array_pair_list`'s empty-member + `rtrim` trick for the trailing comma.

Because it compiles to the official `clone()`, it **inherits all correct behavior
for free**: it calls `__clone()`, and it respects visibility, property hooks, and
`readonly` — `$obj with {…}` is byte-for-byte identical to `clone($obj, [...])`,
including the same errors.

## Caveats / BC

- **`readonly` follows clone-with rules.** A `readonly` property has implicit
  `protected(set)` visibility, so it can only be overridden from a scope where it's
  writable (e.g. an in-class wither), not from outside — same as native clone-with.
- **`with` is semi-reserved.** Still usable as a method name (`$o->with()`), static
  method (`C::with()`), and constant. A *global* `function with()` or class named
  `with` would break (rare). Same trade-off as `unless` / `defer`.

## Tests

- `smoke.sh` — smoke tests
- `tests/with.phpt`
