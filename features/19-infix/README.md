# 19 — Infix function calls

*Borrowed from Haskell's backticks and Kotlin's `infix` functions.*

A bare identifier between two expressions calls the like-named function with the
operands as its two arguments — any two-argument function becomes an operator:

```php
$hi   = 3 max 7;                  // max(3, 7)
$page = 2 pow 10;                 // pow(2, 10)
$ok   = "hello" str_contains "ell";

function clamp(int $x, array $range): int { … }
$speed = $raw clamp [0, 100];     // clamp($raw, [0, 100])

function dist(Point $a, Point $b): float { … }
$d = $p dist $q;                  // reads like maths
```

Haskell's rule, Kotlin's look: as in Haskell (and unlike Kotlin), **no marker is
needed on the declaration** — every function is usable infix, including the
entire standard library.

## Rules

- **Resolution** is exactly that of an ordinary unqualified call: the current
  namespace first, then the global namespace. Undefined names raise the usual
  `Call to undefined function` error.
- **Left-associative:** `1 add 2 add 3` is `add(add(1, 2), 3)`.
- **Tight binding**, mirroring Haskell's default backtick fixity (`infixl 9`):
  an infix call binds tighter than arithmetic, concatenation, and comparison —

  ```php
  2 max 3 * 10      // max(2, 3) * 10   => 30
  "n=" . 2 max 9    // "n=" . max(2, 9) => "n=9"
  1 max 5 > 3       // max(1, 5) > 3    => true
  ```

- Operands are full expressions; `**`, `clone`, casts and unary operators bind
  tighter than the infix name.

## Non-interference

`expr NAME expr` is a **syntax error** in stock PHP — this feature only assigns
meaning to code that previously didn't compile. Constants, function calls,
`instanceof`, ternaries and every other use of identifiers parse exactly as
before; reserved and semi-reserved words (`and`, `or`, `match`, …) are separate
tokens and cannot be infix names.

## How it works

The entire feature is **one grammar production**:

```
expr: … | expr T_STRING expr
```

with `%left T_STRING` giving the bare-identifier token its fixity, desugared in
the parser action to a plain `ZEND_AST_CALL` — no new tokens, no compiler or VM
changes, no opcode. Bison's `%expect 0` still holds: the grammar remains
conflict-free. Qualified names (`$a App\dist $b`) are future scope. See
[`feature.patch`](feature.patch) and the [RFC](RFC.md).

## Try it

```bash
scripts/setup.sh 19-infix && scripts/build.sh
features/19-infix/smoke.sh
```
