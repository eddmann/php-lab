# PHP RFC: `val` write-once local bindings

- **Version:** 0.1
- **Date:** 2026-07-01
- **Author:** Edd Mann <the@eddmann.com>
- **Status:** Draft
- **Target:** PHP 8.5 (experimental fork)
- **Implementation:** [`feature.patch`](feature.patch)

## Introduction

PHP offers `const` only at class/global scope and `readonly` only on properties;
there is no write-once *local*. Borrowing from Kotlin/Scala `val`, Rust `let`, and
Swift `let`, this RFC adds a local binding that is assigned exactly once, with any
later mutation reported as a compile error.

## Proposal

```php
val $config = loadConfig();
echo $config['host'];     // reads are fine
$config = [];             // Fatal: Cannot reassign val $config
```

`val` enforces full immutability of the binding — every compile-time-detectable
write is rejected:

| Form | Example |
| --- | --- |
| reassignment / compound / inc-dec | `$x = …`, `$x += …`, `$x++` |
| assign-by-reference | `$y =& $x`, `$x =& $y` |
| `unset()` | `unset($x)` |
| `foreach` write targets | `foreach (… as $x)` |
| re-declaration | a second `val $x = …` |

Reads, pass-by-value, and iterating `$x` as a `foreach` *source* are always allowed.
An initializer is required.

### Implementation

Compiler-only. A `T_VAL` token and a `val $var = expr;` rule produce a
`ZEND_AST_VAL_DECL` node. The compiler compiles the initializer as an ordinary
assignment, then records the name in a per-scope set on the op-array context. A guard
runs at the top of every variable-write compile path and raises
`E_COMPILE_ERROR "Cannot reassign val $x"`. Because the set lives on the op-array
context (saved/restored at function boundaries), val-ness is per-scope; closures get
their own scope, and a plain variable elsewhere is unaffected. Zero runtime cost.

## Backward Incompatible Changes

- `val` is **semi-reserved** — usable as a method name and class constant, but a
  global `function val()` no longer parses.

## Proposed PHP Version(s)

Next PHP 8.x minor.

## RFC Impact

- **Opcache:** none — the binding compiles to a normal CV assignment.
- **Residual loophole:** passing a `val` to a by-reference parameter
  (`function f(&$x)`) can mutate it; static enforcement there needs a runtime flag,
  out of scope for v1.

## Future Scope

- Runtime enforcement for by-reference escapes; `val` in `foreach` value position.

## Proposed Voting Choices

Yes/No, 2/3 majority.

## Patches and Tests

- [`feature.patch`](feature.patch); [`tests/`](tests/): `val` (reads, scope
  isolation, semi-reserved use) and `val_error` (the compile error), plus the full
  blocked-form matrix.

## References

- Kotlin/Scala `val`; Rust `let`; Swift `let`.
