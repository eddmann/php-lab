# Persistent collections — `Vector` / `Map` / `Set`

> **Inspiration:** Clojure / Scala persistent data structures · **Layer:** engine (C classes) · **Builds on:** nothing (standalone)

## What they are

Three built-in **immutable** collections. Every "mutation" returns a *new*
collection that shares structure with the original — the source is never changed,
and updates are cheap (no full copy).

```php
$a = Vector::of(1, 2, 3);
$b = $a->push(4);      // $a is still Vector(1, 2, 3); $b is Vector(1, 2, 3, 4)

$m = Map::fromArray(['x' => 1]);
$m2 = $m->set('y', 2); // $m unchanged

$s = Set::of(1, 2, 2, 3);   // Set(1, 2, 3)
$u = $s | Set::of(3, 4);    // union: Set(1, 2, 3, 4)
```

| Type | What it is | Keys/members |
| --- | --- | --- |
| `Vector` | ordered, indexed sequence | indices `0..n-1` |
| `Map` | key → value map | `int` or `string` keys |
| `Set` | set of unique members | `int` or `string` members |

## API (highlights)

- **Vector**: `of`/`fromArray`/`new`, `count`/`isEmpty`/`get`/`first`/`last`,
  `push`/`pop`/`set`, `map`/`filter`/`reduce`/`slice`/`concat`,
  `toArray`/`getIterator`/`__toString`, and `+` (concatenation).
- **Map**: `fromArray`/`new`, `count`/`isEmpty`/`has`/`get` (with default),
  `set`/`remove`, `keys`/`values`/`map`/`filter`, `toArray`/iteration.
- **Set**: `of`/`fromArray`/`new`, `count`/`isEmpty`/`has`, `add`/`remove`,
  `union`/`intersect`/`diff` (also `|` / `&` / `-`), `map`/`filter`, `toArray`.

All three implement `IteratorAggregate`, `Countable`, and `Stringable`. The
immutable-update method is named `set` (not `with`) because `with` is a keyword in
this fork.

## Why

PHP only has mutable arrays (and `ext-ds`, also mutable). Functional code wants
values that never change out from under you and can be updated cheaply — the basis
for safe sharing, undo/history, and structural equality. These are the building
blocks the rest of the FP features (`for {} yield`, etc.) compose with.

## How it works — true structural sharing, for free

The clever part: **every trie node is an ordinary refcounted, copy-on-write
`zend_array`.** That means structural sharing, recursive free, and cycle GC all come
from the engine itself — no hand-rolled refcounting.

- **Vector** is a bit-partitioned trie (branching factor 32). A `push`/`set`/`pop`
  *path-copies* only the spine from root to the affected leaf via `zend_array_dup`;
  every untouched sibling subtree is refcount-bumped (shared), not deep-copied — so
  updates are O(log₃₂ n) and old versions keep working.
- **Map** and **Set** are hash-array-mapped tries (HAMT): a fixed-depth trie indexed
  by 5-bit chunks of the key's hash, bottoming out in ordinary assoc-array buckets
  keyed by the real key — O(1) expected `get`/`set`. `Set` is the same trie storing
  each member as its own key, with `|`/`&`/`-` wired through the engine's
  `do_operation` object handler.

Because `zend_array_dup` does `Z_ADDREF` + `ZVAL_COPY_VALUE` on nested arrays (it
shares, never deep-copies), this is genuine structural sharing — verified at 3000+
elements with the source left intact after deep updates.

## Caveats

- **Map/Set keys are `int` or `string` only** (anything else is a `TypeError`).
  Unlike PHP arrays, `Map` keeps `1` and `"1"` as **distinct** keys.
- **Map/Set iterate in hash order**, not insertion order (they're HAMTs).
- The reserved class names `Vector`, `Map`, `Set` shadow userland classes of the
  same name.
- v1 is structurally shared but not maximally compact (HAMT nodes aren't
  popcount-compressed) — correctness and sharing first, density later.

## Tests

- `smoke.sh` — smoke tests across all three types
- `tests/persistent_vector.phpt`, `persistent_map.phpt`,
  `persistent_set.phpt`
