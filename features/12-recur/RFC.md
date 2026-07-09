# PHP RFC: `recur` and automatic self tail-call optimization

- **Version:** 0.1
- **Date:** 2026-07-02
- **Author:** Edd Mann <the@eddmann.com>
- **Status:** Draft
- **Target:** PHP 8.5 (experimental fork)
- **Implementation:** [`feature.patch`](feature.patch)

## Introduction

PHP allocates a stack frame per call and performs no tail-call optimization, so
recursive style is impractical: a tail-recursive function at depth 10⁶ exhausts
memory. Borrowing Clojure's design, this RFC adds an explicit `recur(...)` form
that re-enters the enclosing function with new parameter values on constant stack —
plus an automatic optimization that applies the same rewrite to plain
`return f(...)` self-calls, giving PHP genuine self-TCO with no new syntax.

## Proposal

### Explicit `recur`

```php
function sum_to(int $n, int $acc) {
    if ($n === 0) return $acc;
    return recur($n - 1, $acc + $n);   // rebind params, jump to top
}
sum_to(1_000_000, 0);                   // constant stack
```

- Valid **only in tail position**: `recur(...);` as a statement or
  `return recur(...);`. Any other placement is a compile error.
- Takes exactly one argument per declared parameter. All arguments are evaluated
  before rebinding (so `recur($b, $a)` swaps).
- Allowed in named functions, methods, and closures. Not allowed in generators,
  variadic functions, functions with by-reference parameters, or inside
  `try`/`catch`/`finally`.

### Automatic self-TCO

In `return f(args);` where `f` statically names the **enclosing plain function**
and the call is simple (positional, arity == parameter count), the compiler applies
the identical rewrite silently. Methods are excluded so subclass overrides keep
dynamic dispatch; any ineligibility silently falls back to an ordinary call.

## Semantics

`recur` behaves as: evaluate all arguments; assign them to the parameters; transfer
control to the first statement of the function body. It is a function-level loop
continue — equivalent to the standard accumulator-loop transformation, performed by
the compiler.

## Implementation

Confined to `zend_compile.c` (~140 lines):

- `recur` is recognised **by name** at the two sanctioned compile sites; no new
  token or grammar rule exists, so the parser is untouched.
- Emission: per argument `QM_ASSIGN` into a fresh temporary, then assignments onto
  the parameter CVs, then `zend_handle_loops_and_finally()` (identical to `goto`'s
  loop-teardown), then `ZEND_JMP` to the first opline after the `RECV` sequence.
- The automatic path is a conservative eligibility check in `zend_compile_return`
  sharing the same validator and emitter.

## Backward Incompatible Changes

- `recur` is reserved as an **unqualified function-call name**. Calling a global
  function named `recur()` no longer parses as an ordinary call. Method calls
  (`$o->recur()`) and qualified/variable calls are unaffected.
- Auto-TCO collapses self-recursive frames in `debug_backtrace()` and exception
  traces (one frame instead of N) — the standard observable effect of TCO.

## Proposed PHP Version(s)

Next PHP 8.x minor.

## RFC Impact

- **Opcache:** emits only existing opcodes (`QM_ASSIGN`, `ASSIGN`, `JMP`);
  optimizer passes see an ordinary loop shape.
- **Debugger/traces:** self-TCO'd frames do not appear individually.

## Future Scope

- General tail calls (to other functions) via frame-reuse — requires arg-lifetime
  and destructor-ordering surgery in the VM.
- A `trampoline()` helper for mutual recursion.
- Loop-target form (`loop { } recur(...)`) as in Clojure.

## Proposed Voting Choices

Yes/No, 2/3 majority.

## Patches and Tests

- [`feature.patch`](feature.patch) — one source file + seven `.phpt` tests
  covering depth (200k), swap safety, methods/closures, foreach interaction,
  auto-TCO eligibility (incl. preserved method dispatch), and all five compile
  errors.

## References

- Clojure `recur`: https://clojure.org/reference/special_forms#recur
- Scheme proper tail calls: R7RS §3.5
