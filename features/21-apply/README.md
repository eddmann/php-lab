# 21 — `apply` builder blocks

*Borrowed from Kotlin's scope functions (`apply` / lambda-with-receiver).*

`apply (EXPR) { … }` runs a block with **`$this` bound to `EXPR`**, and the whole
expression **evaluates to `EXPR`**. Inside the block, surrounding variables are
captured automatically (by value), so nested configuration and markup read
top-down without `use` clauses or a trail of temporaries:

```php
$server = apply (new Server()) {
    $this->host = $host;              // $host captured from the surrounding scope
    $this->port = 8080;
    $this->routes[] = apply (new Route()) {   // nested: $this is now the Route
        $this->path = "/health";
        $this->handler = $healthCheck;
    };
};
```

`$server` is the fully-built `Server`. It is Kotlin's `apply`: *configure an object
and hand it back*.

## Semantics

- **`$this` is the receiver** inside the block — even inside a class method, where
  `$this` would otherwise be the enclosing object.
- **The block's value is the receiver.** `apply` returns `EXPR`, so it drops
  straight into an assignment, an array literal, or (parenthesised) a call chain.
- **Surrounding variables are captured by value** at the `apply` point (arrow-
  function semantics). Locals declared *inside* the block stay inside it.
- **The receiver is evaluated once.**
- **Nesting rebinds `$this`** to each inner receiver.

Like `match () { … }` and closures, an `apply` block is not directly
dereferenceable — wrap it in parentheses to chain:

```php
$total = (apply (new Cart()) { $this->add($item); })->total();
```

## How it works

`apply` is a semi-reserved keyword (still usable as a method/constant name). The
block lowers to a self-invoking closure bound to the receiver:

```php
apply (EXPR) { STMTS }
// becomes
(function () { STMTS; return $this; })->call(EXPR)
```

`Closure::call()` binds `$this` to the receiver and returns whatever the closure
returns — here `$this`, i.e. the receiver. The synthesised closure is flagged so
the compiler imports free variables **implicitly**, exactly like an arrow function
(PHP's arrow-function capture machinery is reused). No new opcode, no new runtime
class — a pure front-end desugar in `zend_compile_apply`. See
[`feature.patch`](feature.patch) and the [RFC](RFC.md).

## Scope (v1)

- The receiver must be an **object** (`Closure::call` requires an object `$this`);
  `apply (5) { … }` is a `TypeError`.
- `apply` returns the **receiver** (Kotlin `apply`). A `with`/`run` variant that
  returns the block's own value is left as future scope.
- Bare method calls inside the block still need `$this->` — implicit-receiver
  member calls (Kotlin's `p("hi")` for `this.p("hi")`) are future scope.

## Try it

```bash
scripts/setup.sh 21-apply && scripts/build.sh
features/21-apply/smoke.sh
```
