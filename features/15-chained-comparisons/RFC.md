# PHP RFC: Chained comparisons

- **Version:** 0.1
- **Date:** 2026-07-09
- **Author:** Edd Mann <the@eddmann.com>
- **Status:** Draft
- **Target:** PHP 8.5 (experimental fork)
- **Implementation:** [`feature.patch`](feature.patch)

## Introduction

Range checks are written twice in PHP: `$lo <= $x && $x <= $hi`. The bound `$x`
appears twice, which is verbose and a place for copy-paste bugs. Borrowing from
Python, this RFC lets relational operators **chain**, so `$lo <= $x <= $hi` means
what it does in mathematics.

## Proposal

A run of the relational operators `< <= > >=` is interpreted pairwise:

```php
a op1 b op2 c   ≡   a op1 b && b op2 c
```

extended to any length, with these guarantees:

- each operand is evaluated **exactly once**, left to right;
- the chain **short-circuits** — a false link skips the remaining operands;
- the result is a boolean.

```php
0 <= $i < count($xs);   // 0 <= $i && $i < count($xs)
1 < 2 < 3;              // true
5 < 2 < rhs();          // false; rhs() is never evaluated
5 > 3 < 4;              // operators may be mixed
```

## Backward Incompatible Changes

**None for currently-valid code.** PHP's relational operators are
non-associative, so `a < b < c` is a *syntax error* today; this RFC only assigns
meaning to code that does not currently compile.

Scope is deliberately limited to the relational tier:

- The equality tier (`== != === !== <=>`) is left non-associative — `1 == 1 == 1`
  remains a parse error — so no `==`-chain silently changes meaning.
- Cross-precedence expressions are unaffected: `1 < 2 == true` is still
  `(1 < 2) == true` because `<` binds tighter than `==`.

## RFC Impact

- **Opcache:** none. The lowering happens in the front end and emits ordinary
  `&&`, comparison and assignment opcodes; there is no new opcode.
- **Tokenizer:** unchanged — no new token.
- **Grammar:** the four relational operators change from non-associative to
  left-associative (a precedence-declaration change).

## Implementation

1. Relational operators become `%left` so a chain parses as a left-nested tree.
2. In the compiler, a relational node whose left child is also relational is
   rewritten to the chained `&&` form; each middle operand is bound once to a
   hidden, interned, NUL-prefixed temporary and reused as the next link's left
   operand. Reusing the existing `&&` compilation provides short-circuiting.

## Open Questions / Future Scope

- Extending chaining to the equality tier and to `instanceof`, matching Python's
  broader chaining, would be a larger and more BC-sensitive change and is out of
  scope for v1.

## Proposed Voting Choices

Yes/No, 2/3 majority.

## Patches and Tests

- [`feature.patch`](feature.patch); [`tests/`](tests/): ascending/descending and
  mixed chains, the range idiom, single-evaluation of the middle operand,
  short-circuiting, four-operand chains, float/string operands, preservation of
  mixed-precedence meaning, and the still-rejected equality chain.

## References

- Python comparison chaining: https://docs.python.org/3/reference/expressions.html#comparisons
