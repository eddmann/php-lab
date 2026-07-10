# 20 — `lazy` locals (`lazy val`)

*Borrowed from Scala's `lazy val`.*

A `lazy` local is a variable whose initializer is **not evaluated until the first
time it is read**, and is then **memoised** — computed at most once, no matter how
often it is used (or never, if it is never read):

```php
lazy $conn = connect_to_db();   // does NOT connect yet

if ($needsDb) {
    $conn->query(...);          // connects here, on first read
    $conn->query(...);          // same connection — no re-connect
}
```

It sits between the neighbours it is *not*: [`val`](../06-val/) is a write-once
**eager** local; [`#[Memoize]`](../09-memoize/) caches a **function's** results by
arguments; [lazy parameters](../18-lazy-params/) defer a **call argument** until
the parameter is used. This feature defers and memoises a **single local's** value
(`lazy $x = EXPR;`). It and `18-lazy-params` both explore "lazy" via the `lazy`
keyword — as independent, single-purpose patches — from different angles.

## Semantics

- **Deferred:** the initializer runs on the first read, not at the declaration.
- **Memoised:** subsequent reads return the cached value without recomputing.
- **Never read → never run:** unused `lazy` locals cost nothing.
- **Capture by value at the declaration point** (arrow-function semantics): the
  initializer sees the values variables had when the `lazy` line executed.

```php
$a = 10;
lazy $s = $a + 1;   // captures $a = 10
$a = 999;
echo $s;            // 11
```

A `lazy` initializer may read an earlier `lazy` local; it is forced on demand:

```php
lazy $base    = expensive();
lazy $doubled = $base * 2;   // forces $base only when $doubled is first read
```

## Read-only binding

A `lazy` local is a read-only binding — rebinding it is a compile error:

```php
lazy $x = 1;
$x = 2;    // Error: Cannot modify lazy variable $x (lazy locals are read-only)
$x += 1;   // ditto
$y = &$x;  // aliasing just forces $x into a detached copy; $x is untouched
```

Mutating **through** the value is allowed when the value is an object (objects are
handles):

```php
lazy $cfg = new Config();
$cfg->debug = true;   // forces $cfg, then sets the property — fine
```

Because arrays and scalars are value types, writing to an *element* of a lazy
array (`$lazyArr[$k] = …`) writes to a forced temporary and is lost — read it into
an ordinary variable first if you need to mutate it.

## How it works

`lazy` is a semi-reserved keyword (still usable as a method/constant name). The
statement `lazy $x = EXPR;` lowers to:

```php
$x = new LazyCell(fn() => EXPR);
```

and every **read** of `$x` in the function is compiled into `$x->__force()`, which
runs the thunk once, caches the result, drops the thunk, and returns the value
thereafter (a circular self-force throws instead of looping). `LazyCell` is a
small engine class in its own file (`Zend/zend_lazy.c`), so the feature is
self-contained. Lazy names are tracked per function (`CG(context).lazy_vars`), so
the marker never leaks into nested closures — capture a lazy into a closure by
reading it into a normal variable first. See [`feature.patch`](feature.patch) and
the [RFC](RFC.md).

## Try it

```bash
scripts/setup.sh 20-lazy-val && scripts/build.sh
features/20-lazy-val/smoke.sh
```
