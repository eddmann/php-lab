# `recur` — explicit tail recursion + automatic self-TCO

> **Inspiration:** Clojure `recur` (+ Scheme TCO) · **Layer:** compiler only (one file: `zend_compile.c`)

## What it is

PHP has no tail-call optimization: every recursive call adds a stack frame, so deep
recursion exhausts memory. This feature adds both flavours of the fix:

**1. Explicit `recur(...)`** — rebinds the enclosing function's parameters to new
values and jumps back to the top. Constant stack, any depth:

```php
function sum_to(int $n, int $acc) {
    if ($n === 0) return $acc;
    return recur($n - 1, $acc + $n);   // no new frame — a jump
}
sum_to(1_000_000, 0);                   // fine; plain recursion dies at this depth
```

**2. Automatic self-TCO** — when a *plain function* returns a simple positional
call **to itself**, the compiler applies the same rewrite silently. No keyword:

```php
function fact_iter(int $n, int $acc) {
    if ($n <= 1) return $acc;
    return fact_iter($n - 1, $acc + $n);   // detected and eliminated
}
fact_iter(1_000_000, 1);                    // constant stack
```

## Semantics

- `recur(...)` is valid **only in tail position**: as a statement `recur(...);` or
  as `return recur(...);`. Anywhere else (`$x = recur(...)`) is a compile error.
- One argument per declared parameter — including optional ones. Arguments are all
  evaluated **before** any parameter is rebound, so `recur($b, $a)` swaps correctly.
- Works in named functions, methods, and closures.
- `recur` out of a `foreach` frees the loop iterator (same mechanics as `goto`).

Automatic TCO is deliberately **conservative** — any doubt means an ordinary call:

- **Plain functions only.** A method's self-call keeps dynamic dispatch (a subclass
  override must still win), so methods never auto-TCO — use explicit `recur`.
- Simple positional self-call, arg count == param count, no spread/named args.
- Case-insensitive name match; inside `namespace Foo`, `foo()` matches `Foo\foo`.
- Not in generators, variadic / by-ref-param / by-ref-return functions, or inside
  `try`/`catch`/`finally`.

## How it works

Entirely in `zend_compile.c` — no lexer, parser, AST, or runtime changes:

1. `recur` is intercepted **by name** at the two sanctioned compile sites
   (statement, `return`); any other `recur(...)` call reaching the generic call
   compiler is the tail-position error. No new token, so the grammar is untouched.
2. The rewrite: compile each argument, snapshot it with `QM_ASSIGN` into a
   temporary, assign the temporaries to the parameter CVs, run
   `zend_handle_loops_and_finally()` (frees enclosing loop temporaries, exactly
   like `goto`), then `JMP` to the first opline after the `RECV`s.
3. Auto-TCO is the same emitter behind a silent eligibility check in
   `zend_compile_return`.

## Caveats / BC

- `recur` becomes reserved **as an unqualified function call**. A global function
  named `recur()` can no longer be called as `recur(...)` (define/call it
  namespaced or via a variable if you really need one). Methods named `recur`
  (`$o->recur()`) are unaffected.
- Auto-TCO collapses self-recursive frames, so `debug_backtrace()` shows one frame
  where unoptimized PHP would show N — the standard observable consequence of TCO.
- Locals other than parameters are *not* reset by `recur` (same as a loop body).
- Mutual recursion (`is_even`/`is_odd`) is not eliminated — only self calls.
  A trampoline remains the answer there, as in Clojure.

## Tests

- `tests/recur.phpt` — depth 200k, statement form, swap safety, methods, closures,
  foreach, defaults
- `tests/recur_tco.phpt` — auto-TCO depth, fib untouched, method dispatch
  preserved, defaults fallback, mutual recursion untouched, case-insensitivity
- `tests/recur_error_*.phpt` — the five compile errors
- `smoke.sh`
