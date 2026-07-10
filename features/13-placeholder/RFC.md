# PHP RFC: Placeholder lambdas (`_`)

- **Version:** 0.1
- **Date:** 2026-07-09
- **Author:** Edd Mann <the@eddmann.com>
- **Status:** Draft
- **Target:** PHP 8.5 (experimental fork)
- **Implementation:** [`feature.patch`](feature.patch)

## Introduction

Higher-order functions in PHP are ubiquitous — `array_map`, `array_filter`,
`usort`, `array_reduce`, collection pipelines — but the closures passed to them
carry a lot of ceremony for tiny bodies: `fn($x) => $x * 2`. Borrowing Scala's
underscore syntax (and Raku's Whatever-star `*`), this RFC lets a bare `_` stand
for an implicit parameter, so the body is all you write.

## Proposal

Within a call, any argument containing one or more top-level `_` becomes an arrow
function. Each `_` becomes a positional parameter, numbered left to right:

```php
array_map(_ * 2, $xs);        // fn($p0) => $p0 * 2
usort($xs, _ <=> _);          // fn($p0, $p1) => $p0 <=> $p1
array_reduce($xs, _ + _, 0);  // fn($p0, $p1) => $p0 + $p1
```

The generated function is an arrow function, so free variables are captured by
value:

```php
$k = 100;
array_map(_ + $k, [1, 2]);    // [101, 102]
```

**Binding.** A `_` binds to its innermost enclosing call argument. Placeholders
inside a nested call's argument list bind to that inner call, so pipelines nest
predictably:

```php
array_map(_ + 1, array_map(_ * 10, $xs));
```

A `_` may be the receiver of a method call (`array_map(_->name(), $xs)`). The body
is always the *whole argument*, so a `_` written as the argument of an inner call
(`f(strtoupper(_))`) becomes that inner call's argument rather than lifting
through it; use `strtoupper(...)` for that.

## Backward Incompatible Changes

A bare, unqualified `_` used **directly as a call argument** is now interpreted as
a placeholder rather than a constant read. Reading a userland constant literally
named `_` in argument position (`foo(_)`) would change meaning. This is
considered acceptable: single-underscore constants are extremely rare, the
convention for "ignored"/scratch is the variable `$_`, and gettext's `_()` is a
*function call* (unaffected). A fully-qualified `\_` is never a placeholder, and
`_` outside call arguments is untouched.

## Proposed PHP Version(s)

Next PHP 8.x minor.

## RFC Impact

- **Opcache:** none. The desugar happens before opcode generation and emits an
  ordinary arrow function; there is no new opcode and nothing runtime-specific to
  cache.
- **Tokenizer:** unchanged — no new token. `_` remains `T_STRING`.
- **Reflection / language grammar:** unchanged; the transform is entirely in
  `zend_compile_args`.

## Open Questions / Future Scope

- A wider boundary (Scala also lifts `_` out of some enclosing expressions);
  v1 keeps the simple, predictable "the argument is the body" rule.
- An opt-in different sigil (e.g. `$$`) if collisions with `_` constants prove
  problematic in real code.

## Proposed Voting Choices

Yes/No, 2/3 majority.

## Patches and Tests

- [`feature.patch`](feature.patch); [`tests/`](tests/): unary/binary bodies,
  predicates, reducers, nested-pipeline binding, by-value capture, method-call
  receiver, identity `_`, named arguments, and non-interference with `_()` calls
  and `_` constants.

## References

- Scala placeholder syntax: https://www.scala-lang.org/files/archive/spec/2.13/06-expressions.html#placeholder-syntax
- Raku Whatever `*`: https://docs.raku.org/type/Whatever
