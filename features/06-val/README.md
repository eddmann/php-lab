# `val` — immutable local bindings

> **Inspiration:** Kotlin/Scala `val`, Rust `let` (without `mut`), Swift `let` · **Layer:** lexer + parser + compiler

## What it is

A write-once local: `val $x = EXPR;` declares a variable that is assigned exactly
once. Any later attempt to mutate it is a **compile error**.

```php
val $config = loadConfig();
echo $config['host'];     // reads are fine

$config = [];             // Fatal: Cannot reassign val $config
```

PHP has `const` only at class/global scope; there was no write-once *local*. `val`
fills that gap — and being compile-time, it costs nothing at runtime.

## Why

Local immutability is a daily correctness win: it documents intent, prevents
accidental clobbering, and makes code easier to reason about (a `val` means "this
never changes after here"). It's the local-scope complement to `readonly`
properties.

## What is blocked

`val` enforces **full immutability** — every compile-time-detectable write to the
binding is rejected, not just plain reassignment:

| Form | Example |
| --- | --- |
| reassignment | `$x = …` |
| compound assignment | `$x += …`, `$x .= …`, … |
| increment / decrement | `$x++`, `++$x`, `$x--`, `--$x` |
| assign-by-reference | `$y =& $x` and `$x =& $y` |
| `unset()` | `unset($x)` |
| `foreach` write targets | `foreach (… as $x)` / `as $k => $x` |
| re-declaration | a second `val $x = …` |

Reads are always allowed: using `$x` in expressions, passing it **by value**, or
iterating it as a `foreach` *source*.

## How it works

Compiler-only. A `T_VAL` token and a `val $var = expr;` statement rule produce a
`ZEND_AST_VAL_DECL` node. The compiler (`zend_compile_val_decl`):

1. compiles the initializer as an ordinary assignment **first** (so the declaration
   itself is permitted), then
2. records the variable name in a per-scope set (`val_vars`, a `HashTable` on
   `zend_oparray_context`).

A guard, `zend_check_val_reassign()`, is called at the top of every
variable-write compile path (assign, compound assign, inc/dec, assign-ref, unset,
foreach value/key) and raises `E_COMPILE_ERROR "Cannot reassign val $x"` if the
target is a known `val`. Because `val_vars` lives on the op_array context — saved
and restored at every function boundary — val-ness is **per scope**: the same name
is independent across functions, closures get their own scope, and a plain variable
elsewhere is unaffected.

## Caveats / BC

- **Residual loophole: by-reference function parameters.** Passing a `val` to a
  parameter declared `function f(&$x)` can mutate it, and this can only be caught
  when the callee's signature is known at compile time. v1 does not chase this path
  (dynamic/unknown callees can't be checked statically); true enforcement there
  would need a runtime flag, which is out of scope.
- **`val` is semi-reserved.** It stays usable as a method name (`$o->val()`) and
  class constant (`C::val`). But because it's now a token, a **global**
  `function val()` no longer parses (one upstream engine test that defined such a
  function is renamed in our patch). This is a slightly larger footprint than
  `unless`/`defer`/`with`, since `val` is a more common identifier.
- **Initializer required.** `val $x;` (no value) is not allowed — a write-once
  binding with no value is meaningless.

## Tests

- `smoke.sh` — full blocked-form matrix (one process per compile error)
- `tests/val.phpt` (runtime behavior) and `val_error.phpt` (the
  compile error)
