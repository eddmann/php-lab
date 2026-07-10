# PHP RFC: `lazy` local variables

- **Version:** 0.1
- **Date:** 2026-07-10
- **Author:** Edd Mann <the@eddmann.com>
- **Status:** Draft
- **Target:** PHP 8.5 (experimental fork)
- **Implementation:** [`feature.patch`](feature.patch)

## Introduction

It is common to want a local value that is expensive to compute and only sometimes
needed: a database handle, a parsed configuration, a compiled regex. Today this
means either paying the cost eagerly or hand-rolling a "compute once" closure.
Borrowing Scala's `lazy val`, this RFC adds a `lazy` local whose initializer is
deferred to the first read and memoised thereafter.

## Proposal

```php
lazy $x = EXPR;
```

- `EXPR` is evaluated the first time `$x` is read, then cached; later reads return
  the cached value without recomputing. If `$x` is never read, `EXPR` never runs.
- The initializer captures the enclosing scope **by value** at the declaration
  point (arrow-function semantics).
- A `lazy` local is a **read-only binding**: `$x = …`, `$x += …`, and `&$x` are
  compile errors. Mutating through the value (e.g. a lazy object's property) is
  allowed.

```php
lazy $regex = compile_pattern($src);   // not compiled yet
foreach ($lines as $line) {
    if ($regex->matches($line)) { … }  // compiled once, on first iteration
}
```

## Design

`lazy` is a semi-reserved keyword. The statement lowers to
`$x = new LazyCell(fn() => EXPR)`, and each read of `$x` is compiled to
`$x->__force()`. `LazyCell::__force()` evaluates the thunk once, stores the result,
releases the thunk, and returns the cached value on every later call; a re-entrant
(circular) force throws an `Error` rather than looping. Lazy variable names are
tracked per op_array during compilation, so the transform is scoped to the
declaring function and never affects nested closures.

`LazyCell` lives in its own engine file (`Zend/zend_lazy.c`) and is registered
among the default classes, keeping the feature self-contained.

## Backward Incompatible Changes

- `lazy` becomes a semi-reserved keyword (still usable as a method/constant name).
- `LazyCell` becomes a global class (used only by the desugar).
- No change to existing programs that do not use `lazy`.

## Scope / Future Scope

- **Read-only in v1.** A reassignable lazy (recomputing on demand) is out of scope.
- **Capture by value at the declaration point.** A by-reference / evaluated-at-read
  variant (closer to Scala's environment capture) is possible future work.
- **Not transparently captured into closures.** Reading a lazy into a normal
  variable before capturing is required; auto-forcing at capture sites is future
  work.
- Element writes to a lazy array/string value go to a forced temporary (PHP value
  semantics) and are not reflected back.

## RFC Impact

- **Opcache:** no new opcode. The declaration is an ordinary `new` + arrow
  function; reads are ordinary method calls, all cacheable as usual.
- **Tokenizer:** adds `T_LAZY` (exposed to `token_get_all()` / `token_name()`).

## Proposed PHP Version(s)

Next PHP 8.x minor.

## Proposed Voting Choices

Yes/No, 2/3 majority.

## Patches and Tests

- [`feature.patch`](feature.patch); [`tests/`](tests/): deferral, memoisation,
  never-read, by-value capture, lazy-of-lazy, `isset` without forcing, exception
  propagation, object mutation, read-only enforcement, and the semi-reserved name.

## References

- Scala `lazy val`: https://docs.scala-lang.org/scala3/reference/changed-features/lazy-vals-init.html
