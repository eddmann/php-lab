# PHP RFC: Persistent collections — `Vector`, `Map`, `Set`

- **Version:** 0.1
- **Date:** 2026-07-01
- **Author:** Edd Mann <the@eddmann.com>
- **Status:** Draft
- **Target:** PHP 8.5 (experimental fork)
- **Implementation:** [`feature.patch`](feature.patch)

## Introduction

PHP has only mutable arrays (and the also-mutable `ext-ds`). Functional code wants
values that never change out from under a caller and can be updated cheaply. This
RFC adds three built-in **immutable** collections with structural sharing: `Vector`,
`Map`, and `Set`.

## Proposal

Every "mutation" returns a *new* collection that shares structure with the original;
the source is never changed and updates are cheap.

```php
$a = Vector::of(1, 2, 3);
$b = $a->push(4);          // $a still Vector(1,2,3); $b is Vector(1,2,3,4)

$m = Map::fromArray(['x' => 1]);
$m2 = $m->set('y', 2);     // $m unchanged

$s = Set::of(1, 2, 2, 3);  // Set(1, 2, 3)
$u = $s | Set::of(3, 4);   // union: Set(1, 2, 3, 4)
```

All three implement `IteratorAggregate`, `Countable`, and `Stringable`. `Set`
supports `|`/`&`/`-` (union/intersect/diff). The immutable-update method is named
`set` (not `with`).

### Implementation

Every trie node is an ordinary refcounted, copy-on-write `zend_array`, so structural
sharing, recursive free, and cycle GC come from the engine itself. `Vector` is a
bit-partitioned trie (branching factor 32) whose `push`/`set`/`pop` path-copy only
the spine (O(log₃₂ n)); `Map`/`Set` are hash-array-mapped tries (HAMT) indexed by
5-bit hash chunks (O(1) expected). Implemented in a dedicated engine file
(`zend_persistent.c`); fully independent of other features.

## Backward Incompatible Changes

- `Vector`, `Map`, `Set` become global classes and shadow userland classes of the
  same name.
- `Map`/`Set` keys are `int` or `string` only (`1` and `"1"` are distinct);
  iteration is in hash order, not insertion order.

## Proposed PHP Version(s)

Next PHP 8.x minor.

## RFC Impact

- **Opcache:** none — ordinary internal classes; `|`/`&`/`-` go through the object
  `do_operation` handler.

## Future Scope

- Popcount-compressed HAMT nodes for density; ordered (insertion-preserving) maps.

## Proposed Voting Choices

Yes/No, 2/3 majority.

## Patches and Tests

- [`feature.patch`](feature.patch); [`tests/`](tests/): `persistent_vector`,
  `persistent_map`, `persistent_set` — full API, operators, and structural-sharing
  verification (source intact after deep updates).

## References

- Clojure persistent data structures; Scala immutable collections.
