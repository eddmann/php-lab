# 15 — Chained comparisons

*Borrowed from Python.*

Write a range test the way maths does. `a < b < c` means **`a < b && b < c`** — not
`(a < b) < c` — with each operand evaluated once and the chain short-circuiting:

```php
if (0 <= $i < count($xs)) …        // 0 <= $i && $i < count($xs)
if ($lo <= $temp <= $hi) …
$ok = 1 < 2 < 3;                    // true
```

Any run of the relational operators `< <= > >=` chains, and they can be mixed:

```php
5 > 3 < 4;        // 5 > 3 && 3 < 4   => true
10 >= $n > 0;     // 10 >= $n && $n > 0
```

## Semantics

- **Pairwise.** `a op1 b op2 c` is `(a op1 b) && (b op2 c)`, extended to any length.
- **Each operand once.** The shared middle operands are evaluated a single time,
  left to right — `0 < mid() < 10` calls `mid()` exactly once.
- **Short-circuit.** As soon as a link is false the rest is skipped, so later
  operands may not be evaluated at all: in `5 < 2 < rhs()`, `rhs()` never runs.
- The result is always a boolean.

## What does *not* change (no BC break)

In stock PHP the relational operators are **non-associative**, so `a < b < c` was a
**syntax error** — this feature only gives previously-illegal code a meaning, so
nothing that compiles today changes:

- The **equality tier** (`== != === !== <=>`) is untouched and stays
  non-associative: `1 == 1 == 1` is still a parse error.
- **Mixed-precedence** expressions keep their meaning: `1 < 2 == true` is still
  `(1 < 2) == true`, because `<` binds tighter than `==`.

## How it works

Two small changes, no new tokens or opcodes:

1. **Grammar:** `< <= > >=` become left-associative (they were non-associative),
   so `a < b < c` parses as `((a < b) < c)` instead of erroring.
2. **Compiler:** when a relational node's left operand is itself relational, the
   whole left-nested run is lowered to the chained `&&` form, stashing each middle
   operand in a hidden write-once temporary so it is evaluated once and reused:

   ```
   a < b <= c   ==>   a < ($t1 = b) && $t1 <= c
   ```

   The temporaries are NUL-prefixed, interned, and unreachable from userland.
   Reusing ordinary `&&`, comparison and assignment nodes gives the short-circuit
   and single-evaluation behaviour for free. See [`feature.patch`](feature.patch)
   and the [RFC](RFC.md).

## Try it

```bash
scripts/setup.sh 15-chained-comparisons && scripts/build.sh
features/15-chained-comparisons/smoke.sh
```
