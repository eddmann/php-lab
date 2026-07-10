# 16 — Spread-dot (`*->`)

*Borrowed from Groovy's spread-dot operator.*

`$coll*->method(args)` calls `method` on **every** element of a collection and
collects the results into an array. `$coll*->prop` does the same for a property
read. It turns a loop into a single expression:

```php
$users*->getName();       // [ $u->getName() for each $u ]
$users*->name;            // [ $u->name    for each $u ]
$orders*->total(withTax: true);
```

## Behaviour

- **Maps over the collection.** The result is an array of one result per element.
- **Keys are preserved** — `["x" => $a, "y" => $b]*->name` yields
  `["x" => …, "y" => …]`.
- **Chains.** Each stage returns an array, which the next `*->` maps again:

  ```php
  $boxes*->inc()*->get();   // inc() on each, then get() on each
  ```

- **Left to right, once per element.** For `$coll*->m()`, `m()` runs on each
  element in order.
- The left operand may be any expression: `(getUsers())*->name`.

## How it works

A new `*->` token and a pure parser desugar — no new opcode, no compiler or VM
changes. `$coll*->m(args)` is rewritten to a plain
[`array_map`](https://www.php.net/array_map) over an arrow function:

```php
$coll*->m(a, b)   ==>   array_map(fn($__each) => $__each->m(a, b), $coll)
$coll*->prop      ==>   array_map(fn($__each) => $__each->prop,     $coll)
```

`array_map` is resolved in the global namespace, so it works unchanged from inside
a namespace. Ordinary `*`, `**` and `*=` are untouched — `*->` is only recognised
as a single token when the `*` is immediately followed by `->`. See
[`feature.patch`](feature.patch) and the [RFC](RFC.md).

## Scope of this experiment (v1)

- **Array collections.** Because it lowers to `array_map`, the left operand must be
  an array; a non-array raises `array_map()`'s `TypeError`. For a `Traversable`,
  wrap it with `iterator_to_array(...)` first. (A future version could target a
  helper that also accepts iterables.)
- **Arguments are evaluated per element** (they live inside the mapped closure), so
  keep side-effecting argument expressions in mind — `$coll*->m(next())` calls
  `next()` once per element.
- There is no null-safe form (`*?->`); elements are expected to be objects.

## Try it

```bash
scripts/setup.sh 16-spread-dot && scripts/build.sh
features/16-spread-dot/smoke.sh
```
