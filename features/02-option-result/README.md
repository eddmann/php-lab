# Feature 02 — Algebraic `Option` / `Result` (and everything that consumes them)

> This canonical feature bundles the built-in `Option`/`Result` types, the `?`
> propagation operator, and the seven ways the rest of the language consumes them.
> Each section below was originally written as its own note; they share one patch
> ([`feature.patch`](feature.patch)) because none is separable from the types.

## Contents
- `?` propagation operator + built-in `Option` / `Result`
- `??` made `Option` / `Result`-aware
- `match` destructuring for `Option` / `Result`
- `if let`-style binding via `instanceof`
- `Option` / `Result` combinator API
- `Option` / `Result` factories & interop
- Static analysis — generic `Option<T>` / `Result<T, E>`


---

# `?` propagation operator + built-in `Option` / `Result`

> **Inspiration:** Rust (`?`), the `Option`/`Result` types · **Layer:** compiler + engine · **Commit:** `e289521`

This is the **foundation** the rest of the project builds on. It introduces two
built-in algebraic types and a postfix operator that threads success/failure
through a function without explicit branching.

## The types

`Option` and `Result` are **built-in engine classes** written in C (declared via
`Zend/zend_option_result.stub.php`, implemented in `Zend/zend_option_result.c`).
They are always available — no `require`, no Composer.

- `Option`: `Some($value)` or `None`
- `Result`: `Ok($value)` or `Err($error)`
- Both implement a shared `Propagatable` interface (`isFailure()` / `getValue()`).

Construct them via static factories or the global helper functions:

```php
Some(42);  Option::some(42);
None();    Option::none();
Ok($v);    Result::ok($v);
Err($e);   Result::err($e);
```

## The `?` operator

A postfix `?` short-circuits the **enclosing function**: if the operand is a
failure (`None` / `Err`) the function returns that failure immediately; otherwise
the expression unwraps to the contained value.

```php
function firstName(int $id): Option {
    $user = findUser($id)?;   // if None, firstName() returns that None right here
    $name = $user->name()?;   // chains naturally
    return Some($name);
}
```

`EXPR?` evaluates `EXPR` exactly once. If it `isFailure()`, the enclosing function
returns it — running `finally` blocks, loop cleanup, and the declared return-type
check first, exactly like a normal `return`. Otherwise the expression becomes
`EXPR->getValue()`.

## How it works

This one reaches into the **compiler and engine**:

- A new AST node `ZEND_AST_PROPAGATE` and a postfix `?` grammar rule. The rule
  carries a deliberately low precedence so the **ternary** operator always wins
  wherever the two could collide — this keeps bison's `%expect 0` (zero grammar
  conflicts) intact. `?:`, `??`, and `?->` are completely unaffected.
- `zend_compile_propagate()` emits the *evaluate-once → branch → RETURN-or-unwrap*
  opcode sequence, modelled on the engine's existing null-coalescing-assignment
  machinery.
- The types and `Propagatable` interface are registered as core engine classes at
  startup.

## Caveats / BC

- **Parenthesize before `+` / `-`.** `$x = f()? + 1;` parses as a (malformed)
  ternary; write `(f()?) + 1`. In assignment, `return`, call arguments, and array
  elements (`$u = find()?;`) no parens are needed.
- **Reserved names.** `Some`, `None`, `Ok`, `Err`, `Option`, `Result`, and
  `Propagatable` are now global classes and shadow any userland classes of the
  same name. (Two upstream engine tests that used `Some`/`ok` as ordinary class
  names are renamed in our patch so the Zend suite stays green.)

## Related features

The combinator API and factories/interop sections below extend these types; the
`??`, `match` destructuring, and `instanceof` binding sections (also below) consume
them, as do the comprehensions in [feature 03](../03-comprehensions/).

## Tests

- `smoke.sh` — smoke tests
- `tests/propagate.phpt`, `propagate_return_type.phpt`

---

# `??` made `Option` / `Result`-aware

> **Layer:** compiler · **Commit:** `a69ce07` · **Builds on:** the `?` propagation operator & types (above)

## What it is

The existing null-coalescing operator `??` now also understands `Propagatable`
values. `$opt ?? $default`:

- unwraps a `Some($x)` / `Ok($x)` to its inner value `$x`,
- falls back to `$default` on `None` / `Err` (or on `null`, as before).

```php
$name = findName($id) ?? "anonymous";   // Some("Ann") -> "Ann"; None -> "anonymous"
```

This makes `??` a uniform "give me the value or a fallback" operator across both
`null` and the new option/result types.

## Why

Without this, you'd reach for `->unwrapOr($default)` on the types and `??` on
nullables — two spellings for the same intent. Folding option/result into `??`
keeps a single, familiar idiom.

## How it works

Implemented in `zend_compile_coalesce()`:

1. The existing `ZEND_COALESCE` opcode still performs the `null` / `isset` check
   unchanged.
2. On the non-null path, a new step does an `instanceof Propagatable` test:
   - if it matches, unwrap via `getValue()`;
   - if it's a failure, branch to the `$default` expression instead.

Plain non-null scalar values and the **lazy** evaluation of `$default` (it's only
evaluated when actually needed) are preserved.

## Caveats / BC

- `??=` (null-coalescing **assignment**) is intentionally left unchanged for now —
  only the read form `??` is option/result-aware.
- A `Some(null)` unwraps to `null` (the value is genuinely `null`); this is correct
  but worth keeping in mind if you chain another `??`.

## Tests

- `tests/coalesce_option.phpt`

---

# `match` destructuring for `Option` / `Result`

> **Inspiration:** Rust / Scala pattern matching · **Layer:** compiler · **Commit:** `44910c6` · **Builds on:** the `?` propagation operator & types (above)

## What it is

`match` arms can destructure an `Option` / `Result`, binding the wrapped payload to
a variable for use in the arm body:

```php
$msg = match ($result) {
    Ok($value)  => "ok: $value",
    Err($error) => "failed: $error",
};

$n = match ($opt) {
    Some($x) => $x,
    None     => 0,
};
```

## Patterns

| Pattern        | Matches            | Binds                |
| -------------- | ------------------ | -------------------- |
| `Some($x)`     | a `Some`           | `$x = getValue()`    |
| `Ok($v)`       | an `Ok`            | `$v = getValue()`    |
| `Err($e)`      | an `Err`           | `$e = getError()`    |
| `Some` / `Ok` / `Err` / `None` | by type | nothing      |

Plain value arms (`1 => …`) and a `default` arm can be freely mixed with pattern
arms. A non-matching subject with no `default` still raises `UnhandledMatchError`,
exactly like ordinary `match`.

## Why

Destructuring at the match site removes the unwrap dance (`$result->isOk()` then
`$result->getValue()`) and reads like the pattern matching in Rust/Scala that the
option/result types are borrowed from.

## How it works

A **separate sequential compiler**, `zend_compile_match_patterns()`, runs **only**
when an arm is a pattern. Ordinary `match` (all-scalar arms) stays byte-for-byte
unchanged, so there is no risk of regressing existing code.

The clever part: a pattern arm like `Some($x)` parses as an ordinary
function-call / constant expression, and is **reinterpreted** in arm position.
This is safe because comparing a `match` subject against a freshly-built `Some(...)`
via `===` would never be meaningful anyway, so no real program loses behavior.

The bound variable follows PHP's normal function scope — it remains set after the
`match` completes, just like a `foreach` loop's value variable.

## Caveats

- The bound variable leaks into the enclosing scope (consistent with PHP's scoping
  rules, but different from Rust where the binding is arm-local).

## Tests

- `tests/match_patterns.phpt`

---

# `if let`-style binding via `instanceof`

> **Inspiration:** Rust `if let` · **Layer:** compiler · **Commit:** `a153089` · **Builds on:** the `?` propagation operator & types (above)

## What it is

`instanceof` against an `Option` / `Result` type can capture the unwrapped payload,
so a successful type test also binds a variable. It's Rust's `if let`, spelled with
an **existing keyword** (no new reserved word, no new token):

```php
if ($result instanceof Ok($value)) {
    use_it($value);
} elseif ($result instanceof Err($error)) {
    log($error);
}

while (array_shift($queue) instanceof Some($job)) {
    run($job);
}
```

## Patterns

- `Some($x)` / `Ok($v)` bind `getValue()`
- `Err($e)` binds `getError()`
- Plain `instanceof Some` (no capture parens) is unchanged.

## Why

It's the conditional counterpart to `match` destructuring (above):
when you only care about one variant, `if (... instanceof Some($x))` is more
direct than a full `match`, and it composes with `elseif` / `while` / `&&`.

## How it works

A new AST node `ZEND_AST_INSTANCEOF_BIND` plus a conflict-free grammar rule (bison
`%expect 0` preserved). It is compiled in `zend_compile_instanceof_bind()`:

- The whole construct remains a **boolean expression** that evaluates to the
  `instanceof` result, so it drops into any conditional position.
- On a true result, the payload is unwrapped and assigned to the named variable
  before the branch body runs.
- Binding **requires** a `Some` / `Ok` / `Err` class name; using capture syntax
  against any other class is a compile error.

## Caveats

- Like `match` destructuring, the bound variable follows normal PHP function
  scope and remains visible after the conditional.

## Tests

- `tests/instanceof_bind.phpt`

---

# `Option` / `Result` combinator API

> **Inspiration:** Rust / Scala / functional standard libraries · **Layer:** engine (C methods) · **Commit:** `cbb60fa` · **Builds on:** the `?` propagation operator & types (above)

## What it is

A full method-level functional toolkit on the built-in types, so you can transform
values **without unwrapping** and re-wrapping by hand. All methods are implemented
in C in `Zend/zend_option_result.c`.

```php
$total = Some($cart)
    ->filter(fn($c) => !$c->isEmpty())
    ->map(fn($c) => $c->total())
    ->unwrapOr(0);

$user = findUser($id)                       // Result
    ->flatMap(fn($u) => $u->loadProfile())  // Result
    ->mapErr(fn($e) => "load failed: $e")
    ->toOption();                           // Option
```

## The methods

**Transform**
- `map(callable)` — apply to the contained value, re-wrap.
- `flatMap(callable)` — apply a function that itself returns a wrapper (no nesting).
- `filter(callable)` — `Some` → `None` if the predicate fails (Option).
- `mapErr(callable)` — transform the error channel (Result).
- `orElse(callable)` — supply an alternative wrapper on failure.

**Extract**
- `unwrap()` — the value, or throw on failure.
- `expect(string)` — `unwrap()` with a custom message.
- `unwrapOr(default)` — the value, or a fallback.
- `getValue()` / `getError()` — raw accessors.

**Inspect**
- `isSome()` / `isNone()` / `isOk()` / `isErr()` / `isFailure()`.

**Convert**
- `okOr(error)` — Option → Result.
- `toOption()` — Result → Option.
- `toNullable()` — value or `null`.

## Why

Chaining combinators keeps option/result-heavy code flat and expression-oriented
instead of a staircase of `if ($x->isOk())` checks. It's the everyday ergonomics
layer that makes the types pleasant to actually use.

## Safety

`flatMap` and `orElse` **enforce** that the callback returns the correct wrapper
type — returning a bare value (or the wrong wrapper) is an error, which catches a
common mistake at the source.

## Tests

- `tests/option_result_combinators.phpt`

---

# `Option` / `Result` factories & interop

> **Layer:** engine (C methods) · **Commit:** `b27ffe5` · **Builds on:** the `?` propagation operator & types (above)

## What it is

Constructors and bridges that connect the built-in types to **ordinary PHP** —
exceptions, nulls, arrays, and strings — so the types are usable at the boundaries
of real code, not just in greenfield functions.

```php
$cfg  = Result::try(fn() => json_decode($raw, flags: JSON_THROW_ON_ERROR));
$port = Option::fromNullable($env["PORT"] ?? null)->map(intval(...))->unwrapOr(8080);
$rows = Result::all(array_map(parseRow(...), $lines));   // Ok([...]) or first Err
echo Some(42);                                           // "Some(42)"
```

## The API

**`Result::try(callable): Result`**
Run a callback; return `Ok($returnValue)`, or catch any thrown `Throwable` into
`Err($throwable)`. The bridge from PHP's exception world into the result world.

**`Option::fromNullable(mixed): Option`**
`null` → `None`, anything else → `Some($value)`. The bridge from nullable PHP.

**`Option::all(array): Option` / `Result::all(array): Result`**
Sequence an array of wrappers: if **all** are present, return a wrapper of the list
(**keys preserved**); otherwise return the first `None` / `Err`. Turns "a list of
maybes" into "a maybe of a list".

**`__toString()`**
Readable rendering for logging and interpolation: `Some(42)`, `None`, `Ok("hi")`,
`Err("bad")`.

## Why

The propagation operator and combinators are great *inside* option/result-shaped
code, but real programs sit on top of exception-throwing and null-returning APIs.
These factories are the on-ramps and off-ramps, so you can adopt the types
incrementally without rewriting everything they touch.

## Tests

- `tests/option_result_factories.phpt`

---

# Static analysis — generic `Option<T>` / `Result<T, E>`

> **Layer:** tooling (stub files, no runtime change) · **Commit:** `7fb3150` · **Builds on:** the `?` propagation operator & types (above)

## What it is

The runtime types carry **no generics** — PHP has none at runtime. But
[`option-result.phpstub`](option-result.phpstub) gives PHPStan and
Psalm the generic shapes (`@template`-annotated `Option<T>` and `Result<T, E>`), so
a consuming project gets real type inference and narrowing:

```php
$n = Some(5)->map(fn($x) => $x * 2);   // inferred Some<int>
if ($r instanceof Ok($v)) { /* $v is T */ }
Result::try(fn() => parse($s));        // Result<Parsed, \Throwable>
```

## Wiring it in

For a project running on this PHP build:

```neon
# phpstan.neon
parameters:
    stubFiles:
        - option-result.phpstub
```

```xml
<!-- psalm.xml -->
<stubs><file name="option-result.phpstub"/></stubs>
```

## Why

The whole point of `Option` / `Result` is pushing failure handling into the type
system. At runtime PHP can't express `Option<int>`, but the *analyzer* can — so the
stubs recover most of the static safety (inference, narrowing through `map`/`?`/
`instanceof` binding) while the engine stays dynamic.

## The ceiling

This is as far as type safety goes here: docblock generics for the analyzer, a
dynamic engine underneath. Compile-time **exhaustiveness** checking and a fully
typed error channel would require true runtime generics, which PHP doesn't provide.
The stubs are the pragmatic best-effort.

## Files

- `option-result.phpstub`
