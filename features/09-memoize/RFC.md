# PHP RFC: `#[Memoize]` executable decorator

- **Version:** 0.1
- **Date:** 2026-07-01
- **Author:** Edd Mann <the@eddmann.com>
- **Status:** Draft
- **Target:** PHP 8.5 (experimental fork)
- **Implementation:** [`feature.patch`](feature.patch)

## Introduction

PHP attributes are inert metadata read via reflection. Borrowing the spirit of
Python decorators, this RFC makes one attribute *do something*: `#[Memoize]` rewrites
a function into a memoizing wrapper that caches results by argument, so repeated (and
recursive) calls return the cached value instead of recomputing.

## Proposal

```php
#[Memoize]
function fib(int $n): int {
    return $n < 2 ? $n : fib($n - 1) + fib($n - 2);
}
fib(30); // runs linearly — the body executes once per distinct $n
```

- Results are cached per call, keyed by the serialized arguments.
- The cache is request-scoped and per-function (a `static` inside the wrapper).
- Recursion benefits automatically, because recursive calls go through the public
  name, which is now the wrapper.
- Default and named arguments are forwarded correctly.

### Implementation

A compile-time AST rewrite. When a plain function carries `#[Memoize]`, its original
parameters, body, and return type are moved into an inner closure and the function is
replaced by a thin variadic wrapper holding a `static` cache keyed by
`serialize($args)`. Only ordinary engine machinery is used — a `static` local,
`serialize`/`array_key_exists`, and an immediately-invoked inner closure that still
carries the original signature (so type checks on the real computation are
preserved). No new runtime functions and no cross-request state; the feature is
fully self-contained.

## Backward Incompatible Changes

None. Functions without the attribute are untouched. `Memoize` is recognised as the
attribute name (case-insensitive) at compile time.

## Proposed PHP Version(s)

Next PHP 8.x minor.

## RFC Impact

- **Reflection:** a memoized function reflects as the variadic wrapper, not the
  original parameter list.
- **Scope:** plain functions only in v1 (not methods, closures, generators, or
  by-reference parameters); arguments must be serializable.

## Future Scope

- A general user-implementable `Decorator` interface (needs compile-time "is this
  attribute a decorator?" resolution); methods and closures.

## Proposed Voting Choices

Yes/No, 2/3 majority.

## Patches and Tests

- [`feature.patch`](feature.patch); [`tests/`](tests/): `memoize` — caching, recursion
  speed-up, argument keying, default/named argument forwarding, non-memoized
  functions untouched.

## References

- Python decorators; attribute-driven wrapping.
