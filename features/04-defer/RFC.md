# PHP RFC: `defer` statement

- **Version:** 0.1
- **Date:** 2026-07-01
- **Author:** Edd Mann <the@eddmann.com>
- **Status:** Draft
- **Target:** PHP 8.5 (experimental fork)
- **Implementation:** [`feature.patch`](feature.patch)

## Introduction

Cleanup code in PHP is spread across `finally` blocks that grow awkward when several
resources must be released. Borrowing from Go, this RFC adds `defer` — a statement
that schedules a call to run when the enclosing function exits by **any** path
(return or exception), in **LIFO** order.

## Proposal

```php
function copy(string $src, string $dst): void {
    $in = fopen($src, 'r');
    defer fclose($in);                 // runs on return OR throw
    $out = fopen($dst, 'w');
    defer fclose($out);                // runs before fclose($in) — LIFO
    stream_copy_to_stream($in, $out);
}
```

**Capture semantics (Go-faithful):** the callee and arguments are evaluated at the
`defer` point; only the invocation is delayed.

```php
$x = 1; defer printf("%d\n", $x); $x = 2;   // prints 1
```

The operand must be a function / method / static call; positional and spread
(`...$a`) arguments are supported (named arguments are not, in v1). A non-call
operand is a compile error.

### Implementation

`defer CALL;` lowers to a hidden per-frame collector: `$<hidden> ??= new
DeferScope(); $<hidden>->push(CALLEE(...), [ARGS]);`. First-class-callable syntax
captures the callee immediately; `[ARGS]` captures arguments immediately. The
`DeferScope` object is owned by a compiler-allocated CV with a NUL-prefixed name
unreachable from userland; its `__destruct` replays the collected calls LIFO on both
normal return and exception unwind. `DeferScope` is implemented in its own engine
file (`zend_defer.c`), so `defer` is fully independent of any other feature.

## Backward Incompatible Changes

- `DeferScope` becomes a global class (used only by the desugar).
- `defer` is **semi-reserved** — still usable as a method / constant name.

## Proposed PHP Version(s)

Next PHP 8.x minor.

## RFC Impact

- **Opcache:** uses ordinary FCC + array-literal + method-call opcodes; no new
  opcode. A deferred call runs during teardown (after the return value is computed)
  so it cannot change the return value.
- Throwing from a `defer` is discouraged (destructor-exception rules apply).

## Future Scope

- Named arguments in deferred calls; a `recover()`-style analogue.

## Proposed Voting Choices

Yes/No, 2/3 majority.

## Patches and Tests

- [`feature.patch`](feature.patch); [`tests/`](tests/): LIFO ordering, exception
  path, loops, argument capture, spread, static/method calls, return-value timing,
  non-call error, semi-reserved name.

## References

- Go `defer`: https://go.dev/ref/spec#Defer_statements
