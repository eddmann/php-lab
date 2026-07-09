# Context parameters / implicits — `context` & `provide`

> **Inspiration:** Scala `given`/`using` (+ Kotlin coroutine context, React context, Lisp special variables) · **Layer:** lexer + parser + compiler + runtime

## What it is

Cross-cutting values — a clock, a logger, a DB handle, the current request — usually
have to be **threaded through every function signature** by hand, even functions that
only pass them along. Context parameters let a value be **provided once** and **flow
implicitly down the call chain** to any function that declares it as a `context`
parameter, resolved by type.

```php
class SystemClock { function time(): int { return time(); } }

function now(context Clock $c): int { return $c->time(); }

provide new SystemClock() {     // active for everything called inside the block
    echo now();                 // $c is auto-supplied — no explicit argument
}
```

A `context` parameter is never passed by the caller. The engine resolves it from the
nearest enclosing `provide` whose value matches the parameter's type (by class or
interface).

## Providing values — two forms

**Block form** — active for the dynamic extent of the block (everything it calls):

```php
provide new SystemClock() {
    handleRequest();   // any `context Clock` anywhere below here sees it
}
```

**Statement form** — active for the rest of the enclosing function:

```php
function handler(): void {
    provide new RequestId('abc');   // active from here to the end of handler()
    log('hi');                      // a `context RequestId` deep below is found
}
```

Both push onto a per-request context stack and pop automatically — the block on
leaving the block, the statement when the function returns (or throws).

## Resolution rules

- **Nearest wins.** A nested `provide` of the same type shadows an outer one and is
  restored when it exits.

  ```php
  provide new C('outer') {
      show();                                 // outer
      provide new C('inner') { show(); }      // inner
      show();                                 // outer again
  }
  ```

- **Matched by type.** Resolution walks the stack newest-first and returns the first
  value that is `instanceof` the declared type, so an **interface or base class**
  parameter is satisfied by any matching subtype:

  ```php
  function now(context Clock $c): int { return $c->time(); }
  provide new SystemClock() { now(); }   // SystemClock implements Clock ✓
  ```

- **Missing value is an error** — unless the parameter has a default:

  ```php
  function need(context C $c): void {}
  need();                                    // Error: No context value of type C is available

  function withDefault(context C $c = new C()): void {}
  withDefault();                             // uses the default; no provide needed
  ```

- **Always popped.** A `provide` block that throws still pops its value, so context
  never leaks past it.

## Why

Dynamic-scoped context is the right tool for ambient dependencies that almost every
layer needs but almost no layer wants in its signature: the clock in tests, a
request-scoped logger, the current DB transaction, a correlation/request id. You get
the convenience of a global with the safety of lexical-looking scope — values are
visible only for the extent of a `provide`, and nested scopes compose.

## How it works

A compile-time desugar plus a small runtime stack — no changes to the
parameter-binding / `arg_info` / `RECV` machinery (mirrors the `#[Memoize]` and
`defer` transforms):

- **`context` params → body prologue.** Before a function is compiled, a pre-pass
  (`zend_rewrite_context_params` in `Zend/zend_compile.c`)
  removes each `context T $c` from the signature and **prepends** an initializer to
  the body:
  - no default: `$c = \__ctx_resolve("T");`
  - with default `D`: `$c = \__ctx_has("T") ? \__ctx_resolve("T") : (D);`

  So the public signature is a normal, context-free one — reflection, named
  arguments, and RECV numbering all behave as usual.

- **`provide` → push + scoped pop.**
  - Block `provide V { B }` → `\__ctx_push(V); try { B } finally { \__ctx_pop(); }`.
  - Statement `provide V;` → `\__ctx_push(V); defer \__ctx_pop();` (reusing the
    existing `defer`, so the pop runs at function exit on return *and* exception).

- **Runtime** (`Zend/zend_context.c`). A per-request
  stack `EG(context_stack)` holds the provided objects. `__ctx_resolve` /`__ctx_has`
  walk it newest-first, matching with `instanceof_function` against the looked-up
  class; `__ctx_resolve` throws if nothing matches.

Functions without context params and files without `provide` compile and run exactly
as before — the transform only fires where the keywords appear.

## Caveats / scope (v1)

- **Context values are objects**, matched by class/interface. Scalars aren't
  context-resolvable (there's no type to match on).
- Context params on **named functions, methods, and closures**; not arrow functions
  (they have no block body).
- **Auto-only**: callers never pass a context parameter explicitly; there's no
  per-call override yet.
- The type is resolved by its **written class name** (non-namespaced / fully
  qualified in v1).
- `context` and `provide` are **case-sensitive, lowercase-only keywords** — chosen so
  the very common `class Context {}` (capitalized) keeps working. They're also
  semi-reserved, so they remain usable as method/constant names (`$o->context(...)`,
  `$o->provide()`).

## Tests

- `smoke.sh` — smoke tests
- `tests/context.phpt`
