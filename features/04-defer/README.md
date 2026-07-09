# `defer` — Go-style scope-exit cleanup

> **Inspiration:** Go `defer` · **Layer:** lexer + parser + compiler + runtime · **Commit:** `f261dc6`

## What it is

`defer CALL;` schedules a call to run when the enclosing function returns **by any
path — normal return or exception** — in **LIFO** order. It replaces nested
`try / finally` for cleanup:

```php
function copy(string $src, string $dst): void {
    $in = fopen($src, 'r');
    defer fclose($in);                 // runs on return OR throw
    $out = fopen($dst, 'w');
    defer fclose($out);                // runs before fclose($in) (LIFO)
    stream_copy_to_stream($in, $out);
}
```

## Capture semantics (Go-faithful)

The callee **and** the arguments are evaluated at the `defer` point; only the
invocation is delayed.

```php
$x = 1;
defer printf("%d\n", $x);   // captures 1...
$x = 2;                     // ...so this prints 1, not 2

defer $obj->m();            // captures *this* $obj even if $obj is reassigned later
```

The operand must be a function / method / static call. Positional and spread
(`...$a`) arguments are supported; **named arguments are not** (v1), and any
non-call operand is a compile error.

## How it works

`zend_compile_defer()` desugars each statement to a hidden per-frame collector:

```php
$<hidden> ??= new DeferScope();          // created lazily, once per frame
$<hidden>->push(CALLEE(...), [ARGS]);    // FCC captures callee NOW; [ARGS] eval NOW
```

- `CALLEE(...)` uses **first-class-callable** syntax, which evaluates the
  receiver / closure immediately (Go-faithful callee capture).
- `[ARGS]` is an array literal built from the call's argument list; a spread `...$a`
  is carried over verbatim, so its elements are flattened into the captured array
  at the defer point.
- The hidden storage is a compiler-allocated CV with an internal, **NUL-prefixed
  name** unreachable from userland, so the `DeferScope` is owned solely by the
  frame. Its `__destruct` fires exactly when the frame's locals are freed — on
  normal return **and** during exception unwind — and replays the collected calls
  **LIFO**.

Because the callee and args are captured up front, deferred calls don't depend on
other locals still being alive at teardown.

A runtime detail worth noting: `DeferScope` declares `private array $entries = []`,
whose default is the engine's *immutable* empty array. `SEPARATE_ARRAY` won't clone
it (its refcount is 1), so `push` installs a fresh owned array on the first call and
appends thereafter.

## Caveats / BC

- A defer runs during teardown — **after** the return value is computed — so it
  **can't change the return value**.
- **Don't throw from a defer.** PHP's destructor-exception handling is restrictive,
  and there is no clean `recover()` analogue.
- **`DeferScope`** becomes a global class (used only by the desugar) and so is a
  reserved name.
- **`defer` is only semi-reserved** — a contextual keyword in statement position;
  it stays usable as a method / constant name (no BC break).

## Tests

- `smoke.sh` — smoke tests (LIFO, exception path, loop, capture, spread,
  static call, return value, non-call error, semi-reserved name)
- `tests/defer.phpt`
