# PHP RFC: Lazy (by-name) parameters

- **Version:** 0.1
- **Date:** 2026-07-09
- **Author:** Edd Mann <the@eddmann.com>
- **Status:** Draft
- **Target:** PHP 8.5 (experimental fork)
- **Implementation:** [`feature.patch`](feature.patch)

## Introduction

PHP evaluates every argument before a call, so `debug_log($on, json_encode($huge))`
pays for the encode even when logging is off, and a helper like
`ifThenElse($c, $a, $b)` cannot exist (both branches evaluate). ALGOL 60 solved
this with call-by-name; Scala kept it as `=> T` by-name parameters. This RFC
adds a `lazy` parameter modifier: the argument expression is suspended and only
evaluated if the parameter is actually read.

## Proposal

```php
function debug_log(bool $on, lazy $msg): void {
    if ($on) echo $msg, "\n";     // $msg evaluates here — or never
}
debug_log(false, json_encode($huge));   // encode never runs

function ifThenElse(bool $c, lazy $then, lazy $else) { return $c ? $then : $else; }
function fact(int $n): int { return ifThenElse($n <= 1, 1, $n * fact($n - 1)); }
```

### Semantics

- The suspended expression is **captured at the call site by value**
  (arrow-function capture rules) and **forced at the parameter's first read**;
  the result is **memoized** (call-by-need, like Scala's `lazy val`, rather
  than ALGOL's re-evaluation per read).
- Exceptions raised by the expression surface where it is forced — inside the
  callee, catchable there — and never surface if the parameter goes unread.
- Lazy parameters are **read-only**; writes are compile errors.
  `isset()`/`empty()` observe the raw slot. Closures in the body capture the
  suspension and share its memoization.
- v1 restrictions: no type, no by-ref, no variadic, no default, no promotion;
  first 32 parameters only.

### Degradation rule

PHP resolves most callees at runtime; the compiler thunks an argument only when
it already knows the callee (same-script previously-declared functions — the
common case in practice). Dynamic calls, method calls, named and spread
arguments evaluate **eagerly**, and the in-body force is an identity on plain
values — so behaviour degrades from lazy to eager without ever becoming wrong.
Arguments containing `yield` are deliberately left eager.

## Backward Incompatible Changes

`lazy` becomes a semi-reserved word: it still works as a method, constant, or
function name, but a **class named `lazy` can no longer appear as a parameter
type hint** in this fork. No other currently-valid code changes meaning.

## RFC Impact

- **Opcache:** the thunk and force compile to ordinary `new`, closure and call
  opcodes — no new opcode. Under opcache's file cache the compiler ignores
  same-file function bindings, so laziness degrades to eager per the rule
  above (the lab builds with opcache disabled).
- **Engine:** one new internal class (`LazyThunk`) and function
  (`__lazy_force`) in their own file, `zend_lazy.c`; a `lazy_params` bitmask
  field on `zend_op_array`; a `ZEND_PARAM_LAZY` AST attr bit.
- **Tokenizer:** one new semi-reserved token, `T_LAZY`.

## Open Questions / Future Scope

- Typed lazy parameters (`lazy string $msg`) — requires deferring the type
  check to force time.
- Thunking at dynamic call sites (needs a runtime signature check or a
  declaration-visible calling convention).
- A `lazy` *argument* marker (`f(lazy $expr)`) for explicit call-site control.

## Proposed Voting Choices

Yes/No, 2/3 majority.

## Patches and Tests

- [`feature.patch`](feature.patch); [`tests/`](tests/): unused-argument
  elision, single memoized force, body-before-args ordering, by-value capture,
  deferred exceptions, the assert idiom, recursion through lazy branches,
  closure capture sharing, eager degradation on dynamic calls, read-only
  enforcement, and declaration validations.

## References

- ALGOL 60 call-by-name: https://en.wikipedia.org/wiki/Evaluation_strategy#Call_by_name
- Scala by-name parameters: https://docs.scala-lang.org/tour/by-name-parameters.html
