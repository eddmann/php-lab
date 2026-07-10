# PHP RFC: `apply` builder blocks (lambda-with-receiver)

- **Version:** 0.1
- **Date:** 2026-07-10
- **Author:** Edd Mann <the@eddmann.com>
- **Status:** Draft
- **Target:** PHP 8.5 (experimental fork)
- **Implementation:** [`feature.patch`](feature.patch)

## Introduction

Building and configuring an object in PHP means either a fluent interface (every
method must `return $this`), a constructor with many arguments, or a sequence of
`$obj->…` statements naming the variable over and over. Borrowing Kotlin's
`apply` — a *function literal with receiver* — this RFC adds a block in which
`$this` is the object being built and the block evaluates to that object.

## Proposal

```php
apply (EXPR) { STMTS }
```

- `STMTS` run with `$this` bound to `EXPR`.
- Surrounding variables are captured by value (arrow-function semantics).
- The expression evaluates to `EXPR` (the receiver).

```php
$config = apply (new Config()) {
    $this->debug = $debug;
    $this->cache = apply (new Cache()) {   // nested: $this is the Cache
        $this->ttl = 300;
    };
};
```

This is the object-building member of Kotlin's scope-function family (`apply`,
which returns the receiver). It composes for tree-shaped construction — DOM/HTML
builders, configuration objects, test fixtures.

## Design

`apply` is a semi-reserved keyword. The block lowers to a self-invoking closure
bound to the receiver:

```php
(function () { STMTS; return $this; })->call(EXPR)
```

`Closure::call()` binds `$this` and returns the closure's result (`$this`), so the
expression yields the receiver. The synthesised closure is marked so the compiler
runs the **arrow-function implicit-capture** pass over it, importing free
variables by value without a `use` list. There is no new opcode and no new runtime
class; the entire feature is a front-end desugar.

## Backward Incompatible Changes

- `apply` becomes a semi-reserved keyword (still usable as a method/constant name).
- No change to existing programs that do not use `apply`.

## Scope / Future Scope

- **Object receivers only.** `Closure::call()` requires an object `$this`; a scalar
  receiver is a `TypeError`.
- **Returns the receiver** (Kotlin `apply`). A `with` / `run` variant returning the
  block's own last value is possible future work.
- **Explicit `$this->` for member access.** Kotlin's implicit receiver (bare
  `child { … }` meaning `this.child { … }`) is future scope.
- Like `match`, an `apply` block must be parenthesised to be dereferenced
  (`(apply (…) { … })->method()`).

## RFC Impact

- **Opcache:** no new opcode. The desugar emits an ordinary closure and a method
  call, both cached as usual.
- **Tokenizer:** adds `T_APPLY` (exposed to `token_get_all()` / `token_name()`).

## Proposed PHP Version(s)

Next PHP 8.x minor.

## Proposed Voting Choices

Yes/No, 2/3 majority.

## Patches and Tests

- [`feature.patch`](feature.patch); [`tests/`](tests/): `$this` binding, receiver
  return, by-value capture, nesting, control flow in the block, method-context
  receiver, parenthesised dereference, single receiver evaluation, exception
  propagation, and the semi-reserved name.

## References

- Kotlin scope functions (`apply`): https://kotlinlang.org/docs/scope-functions.html#apply
- Kotlin function literals with receiver: https://kotlinlang.org/docs/lambdas.html#function-literals-with-receiver
