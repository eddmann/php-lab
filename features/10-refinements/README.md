# Refinements — scoped extension & override methods

> **Inspiration:** Ruby refinements (+ Smalltalk/Kotlin extension methods) · **Layer:** lexer + parser + compiler + runtime

## What it is

A `refinement` adds (or overrides) methods on a type — including **scalars and
arrays** — and is only active in files that opt in with `using`. It's the *safe*
monkeypatch: nothing leaks globally, and code you call doesn't inherit your changes.

```php
refinement Str for string {
    function shout(): string { return strtoupper($this) . '!'; }
}

using Str;
echo "hi"->shout();        // "HI!"  — a method call on a scalar
```

Inside a refinement method, **`$this` is the receiver** (the string, int, array, or
object the method was called on).

## What you can refine

- **Scalars & arrays** — `string`, `int`, `float`, `bool`, `array`. The part PHP
  can't do at all today (`"x"->slug()`, `[1,2,3]->sum()`, `42->times(...)`).
- **Add methods to existing classes** — `DateTimeImmutable->humanize()` without
  subclassing.
- **Override existing methods** — scoped: `Money->format()` behaves differently in a
  file that `using`-es the refinement; everywhere else it's unchanged.
- A refinement `for Base` also applies to **subclasses** (the receiver's class
  hierarchy and interfaces are consulted).

```php
class Money { public function __construct(public int $cents) {} function fmt(){ return '$'.$this->cents; } }
refinement Eu for Money {
    function fmt(): string { return $this->cents . ' EUR'; }   // override
    function half(): Money { return new Money((int)($this->cents/2)); } // add
}
using Eu;
echo (new Money(1000))->fmt();   // "1000 EUR"  (only here)
```

## Scope — the whole point

`using X;` activates a refinement **for the rest of the file**. A function defined in
that file keeps the refinement when called from elsewhere (resolution follows where
the call is *written*), but other files don't inherit it:

```php
// helpers.php
using Str;
function loud(string $s): string { return $s->shout(); }   // ✓

// main.php (no `using Str`)
echo loud("hi");      // ✓ "HI!" — the call lives in helpers.php's scope
echo "hi"->shout();   // ✗ Error — not active here
```

That isolation is what makes it safe: two files can refine the same type
differently, and a library never sees your refinements.

## Why

PHP can't put methods on scalars/arrays, and adding behavior to a class you don't
own means subclassing or global helper functions. Refinements give a scoped,
collision-free way to do both — fluent APIs on built-in types, domain methods on
third-party classes, and local overrides for testing/adaptation.

## How it works

- A `refinement R for T { … }` compiles each method into a plain function named by a
  mangled, NUL-prefixed key (`\0refine\0R\0T\0m`). The receiver is injected as the
  first parameter, and the body's `$this` is rewritten to it — so **no engine
  `$this`-on-a-scalar surgery** is needed.
- `using X;` records X in the per-file compile context (`FC(active_refinements)`).
- In a file with active refinements, the compiler rewrites `$recv->m(a, b)` to
  `__refine_call($recv, "m", [active refinements], a, b)`. The runtime helper
  (`Zend/zend_refinements.c`) looks up an active
  refinement matching the receiver's type/method (walking the class hierarchy for
  objects); if found it calls it, otherwise it **falls back to a normal method
  call** — preserving `private`/`protected` visibility by restoring the caller's
  scope (`EG(fake_scope)`).

Files that never say `using` are compiled and run **exactly as before** (the rewrite
only fires when a refinement is active), so there's zero overhead and zero behavior
change outside opted-in files.

## Caveats / scope

- **v1: one target type per refinement** (`for string`, not `for string, int`).
- Only the `->` method-call form is refined; `?->` (nullsafe) and `::` (static) are
  not, and first-class-callable `$x->m(...)` is left as a normal closure.
- **By-reference** parameters/return values aren't preserved through a refined
  call's fallback, and **named arguments** to refined calls aren't supported in v1
  (positional + spread are).
- `refinement` and `using` are semi-reserved (still usable as method/constant
  names).
- In a `using` file, *every* `->` call routes through the dispatcher — a deliberate,
  opt-in cost for that file.

## Tests

- `smoke.sh` — smoke tests
- `tests/refinements.phpt`
