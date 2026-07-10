# 18 — Lazy (by-name) parameters

*Borrowed from ALGOL 60's call-by-name and Scala's `=> T` by-name parameters.*

A parameter marked `lazy` receives the **unevaluated argument expression**. It
is evaluated only if — and when — the parameter is first read, then memoized
(call-by-need). Diagnostics stop costing anything on the happy path:

```php
function debug_log(bool $on, lazy $msg): void {
    if ($on) echo $msg, "\n";      // $msg evaluates here, or never
}
debug_log(false, json_encode($huge));   // json_encode never runs

function assert_that(bool $cond, lazy $msg): void {
    if (!$cond) throw new AssertionError($msg);
}
```

And it makes **user-defined control flow** possible — the untaken branch never
evaluates, so recursion through an argument terminates:

```php
function ifThenElse(bool $c, lazy $then, lazy $else) { return $c ? $then : $else; }
function fact(int $n): int { return ifThenElse($n <= 1, 1, $n * fact($n - 1)); }
fact(10);   // 3628800 — eager evaluation would recurse forever
```

## Semantics

- **Deferred:** the callee's body starts before lazy arguments evaluate; each
  is forced at its first read, in use order.
- **Memoized (call-by-need):** forced exactly once, then cached — Scala's
  `lazy val` behaviour rather than ALGOL's re-evaluate-per-read.
- **Captured at the call site, by value** (arrow-function capture): the
  expression sees the variables as they were when the call was made.
- **Exceptions defer too:** a throwing argument throws where it is *forced*
  (catchable in the callee), and not at all if never read.
- **Read-only:** assigning to (or `++`/`unset`-ing) a lazy parameter is a
  compile error. `isset()`/`empty()` observe the raw slot.
- Closures inside the body capture the suspended argument and share its
  memoization.
- v1 validations: a lazy parameter cannot be typed, by-reference, variadic,
  promoted, or have a default; only the first 32 parameters may be lazy.

## Graceful degradation

PHP resolves most calls at runtime, so the compiler can only thunk arguments
when it can already see the callee (same-file previously-declared functions —
the common case). Everywhere else — dynamic calls (`$fn(...)`,
`call_user_func`), method calls, named/spread arguments — the argument
evaluates **eagerly** and the force is a transparent pass-through: programs
stay correct, they just lose the laziness. (Arguments containing `yield` are
also left eager to preserve generator semantics.)

## How it works

Two cooperating halves, no new opcodes or VM changes:

1. **Call site** (`zend_compile_args`): when the resolved callee's parameter
   `i` is lazy, the plain positional argument is wrapped in
   `new \LazyThunk(fn() => ARG)` — the arrow function gives by-value capture.
2. **Callee body** (`zend_compile_func_decl_ex`): every read of a lazy
   parameter compiles as `\__lazy_force($p)`, a tiny internal function that
   evaluates a `LazyThunk` once (memoizing) and passes any other value through
   unchanged — which is exactly what makes the eager fallback safe.

`LazyThunk` and `__lazy_force` live in their own engine file (`zend_lazy.c`),
registered at startup; the `lazy` keyword is semi-reserved. The parameter flag
travels as an AST attr bit into a per-function `lazy_params` bitmask on the
op_array. See [`feature.patch`](feature.patch) and the [RFC](RFC.md).

## Try it

```bash
scripts/setup.sh 18-lazy-params && scripts/build.sh
features/18-lazy-params/smoke.sh
```
