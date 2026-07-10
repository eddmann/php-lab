# 13 — Placeholder lambdas (`_`)

*Borrowed from Scala's `_` and Raku's Whatever-star `*`.*

A bare `_` inside a call argument turns that argument into a closure, with one
fresh parameter per `_` in left-to-right order. It is the terse anonymous
function — no `fn`, no `=>`, no parameter names:

```php
array_map(_ * 2, $xs);        // fn($p) => $p * 2
array_filter($xs, _ > 0);     // fn($p) => $p > 0
usort($xs, _ <=> _);          // fn($a, $b) => $a <=> $b
array_reduce($xs, _ + _, 0);  // fn($a, $b) => $a + $b
```

## The rule

Within a call, **each argument that contains one or more top-level `_` becomes an
arrow function**. The argument expression is the body; every `_` in it becomes a
positional parameter, numbered in source order. Because the desugar produces an
[arrow function](https://www.php.net/manual/en/functions.arrow.php), surrounding
variables are captured **by value**:

```php
$k = 100;
array_map(_ + $k, [1, 2]);    // [101, 102] — $k is captured
```

### Boundaries — where a placeholder binds

A `_` binds to its **innermost enclosing call argument**. Placeholders that sit
inside a *nested* call's argument list belong to that inner call, so data
pipelines compose the way you would read them:

```php
array_map(_ + 1, array_map(_ * 10, [1, 2, 3]));   // [11, 21, 31]
```

The inner `_ * 10` becomes the inner map's closure; the outer `_ + 1` becomes the
outer map's. A `_` may also be the **receiver** of a method call:

```php
array_map(_->getName(), $users);   // fn($u) => $u->getName()
```

Because the body is *the whole argument*, a `_` nested inside another function
call does not "lift through" it — `array_map(strtoupper(_), $xs)` makes `_` the
argument of `strtoupper`, i.e. `strtoupper(fn($x) => $x)`. Reach for a
first-class callable (`strtoupper(...)`) or an explicit `fn` there instead.

## Non-interference

The placeholder is only recognised as a **bare, unqualified `_` used directly as
a call argument** — everything else keeps working:

| Code | Meaning |
|------|---------|
| `_($x)` | a normal call to a function named `_` (e.g. gettext) — it is a call, not a placeholder |
| `\_` | a fully-qualified constant named `_` — never a placeholder |
| `const _ = 7; echo _;` | a plain constant read **outside** a call argument — untouched |
| `"_"`, `$_` | a string / a variable — unrelated |

The one deliberate trade-off: a *bare* constant named `_` passed directly as a
call argument (`foo(_)`) is read as the identity closure `fn($x) => $x`. Constants
named `_` are vanishingly rare; the gettext **function** `_()` is a call and is
unaffected.

## How it works

Pure front-end desugar in the compiler — **no new tokens, no new opcodes, no
scanner or grammar changes**. A bare `_` already parses as an unqualified
`ZEND_AST_CONST`. In `zend_compile_args`, before the arguments are compiled, each
argument is walked: every placeholder `_` is rewritten *in place* into a `$__phN`
variable node, and the argument is wrapped in a synthesised `ZEND_AST_ARROW_FUNC`
with the matching parameters. The walk stops at own-scope nodes (nested closures,
arrow functions, classes) and at nested calls' argument lists, which is what gives
the binding rule above. See [`feature.patch`](feature.patch) and the
[RFC](RFC.md).

## Try it

```bash
scripts/setup.sh 13-placeholder && scripts/build.sh
features/13-placeholder/smoke.sh
```
