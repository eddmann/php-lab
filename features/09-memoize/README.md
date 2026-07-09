# `#[Memoize]` — executable decorators

> **Inspiration:** Python decorators / attribute-driven wrapping · **Layer:** compiler

## What it is

An attribute that **wraps** a function: a plain function marked `#[Memoize]` is
rewritten by the compiler into a memoizing wrapper that caches results by argument,
so repeated (and recursive) calls return the cached value instead of recomputing.

```php
#[Memoize]
function fib(int $n): int {
    return $n < 2 ? $n : fib($n - 1) + fib($n - 2);
}

fib(30); // exponential by definition, but runs linearly — the body executes
         // once per distinct $n, because recursion re-enters the memoized name
```

PHP attributes are normally inert metadata. This makes one *do something*: it
changes how the function executes.

## Behavior

- Results are cached per call, keyed by the (serialized) arguments; equal arguments
  return the cached result.
- The cache is **request-scoped** and per-function (a `static` inside the wrapper).
- Recursion benefits automatically, because recursive calls go through the public
  function name — which is now the memoizing wrapper.
- Default and named arguments are forwarded correctly.

## How it works

Compile-time AST rewrite in `zend_compile_func_decl_ex`. When a **plain function**
(not a method/closure) carries `#[Memoize]`, the original parameters, body and
return type are moved into an inner closure, and the function is replaced by a thin
wrapper:

```php
function fib(...$__args) {
    static $__memo = [];
    $__key = \serialize($__args);
    if (\array_key_exists($__key, $__memo)) return $__memo[$__key];
    $__memo[$__key] = (function (int $n): int { /* original body */ })(...$__args);
    return $__memo[$__key];
}
```

This uses only ordinary engine machinery — a `static` local for the request-scoped
cache, `serialize`/`array_key_exists` for keying, and an immediately-invoked inner
closure that still carries the original signature (so type checks on the real
computation are preserved). No new runtime functions, no cross-request state.

## Caveats / scope

- **Plain functions only** in v1 (not methods or closures).
- The public wrapper's signature is variadic (`...$__args`); reflection on a
  *memoized* function shows the wrapper, not the original parameters. Non-memoized
  functions are completely untouched.
- Arguments must be **serializable** (scalars, arrays, serializable objects) — the
  cache key is `serialize($args)`. Don't memoize functions taking closures/resources.
- Don't memoize **generators** or **by-reference** parameters (the wrapper forwards
  by value).
- `Memoize` is the recognized attribute name (case-insensitive); it's detected
  syntactically at compile time.
- This is the built-in executable decorator; a general user-implementable
  `Decorator` interface is a natural future extension (it needs compile-time
  "is this attribute a decorator?" resolution, deferred for now).

## Tests

- `smoke.sh` — smoke tests
- `tests/memoize.phpt`
