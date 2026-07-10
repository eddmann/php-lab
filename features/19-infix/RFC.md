# PHP RFC: Infix function calls

- **Version:** 0.1
- **Date:** 2026-07-09
- **Author:** Edd Mann <the@eddmann.com>
- **Status:** Draft
- **Target:** PHP 8.5 (experimental fork)
- **Implementation:** [`feature.patch`](feature.patch)

## Introduction

Binary operations written as function calls read inside-out: `max($a, $b)`,
`str_contains($haystack, $needle)`, `clamp($x, $range)`. Haskell lets any
function be written between its operands with backticks; Kotlin does the same
for functions marked `infix`. This RFC brings that to PHP: a bare identifier
between two expressions calls the like-named function.

## Proposal

```php
$hi = 3 max 7;                       // max(3, 7)
$ok = "hello" str_contains "ell";    // str_contains("hello", "ell")
$speed = $raw clamp [0, 100];        // clamp($raw, [0, 100])
```

- **Any function may be used infix** (Haskell's rule — no declaration marker),
  including internal ones.
- **Resolution** matches an ordinary unqualified call: current namespace first,
  then global. Undefined names raise the usual `Error`.
- **Left-associative**: `1 add 2 add 3` ≡ `add(add(1, 2), 3)`.
- **Fixity** mirrors Haskell's backtick default (`infixl 9`): tighter than
  arithmetic, concatenation and comparison; looser than `**`, unary operators,
  casts and `clone`.
- Reserved and semi-reserved words are separate tokens and cannot be infix
  names; qualified names (`$a App\dist $b`) are future scope.

## Backward Incompatible Changes

`expr IDENT expr` is a syntax error in stock PHP, so **no currently-valid
program changes meaning**. One observable difference in *invalid* programs: a
stray identifier after an expression (e.g. the bad numeric-literal separator in
`100_ 500`) is now consumed as an infix operator, so the parse error is reported
at the *following* token with a different message. The code is rejected either
way; a dozen upstream error-message tests are updated accordingly in the patch.

## RFC Impact

- **Opcache / VM / tokenizer:** none. The desugar emits an ordinary
  `ZEND_AST_CALL` in the parser action; no new token, opcode, or compiler pass.
- **Grammar:** one production (`expr T_STRING expr`) plus a `%left T_STRING`
  fixity declaration. The grammar remains conflict-free (`%expect 0` holds).

## Open Questions / Future Scope

- Qualified (`App\dist`) and method (`$obj->m`) infix forms.
- A Kotlin-style `infix` declaration marker restricting which functions may be
  used infix.
- User-defined fixity (Haskell `infixl 6 …`) instead of one fixed tier.

## Proposed Voting Choices

Yes/No, 2/3 majority.

## Patches and Tests

- [`feature.patch`](feature.patch); [`tests/`](tests/): internal and user
  functions, chaining/associativity, precedence against `* . <`, expression
  operands, namespace resolution, undefined-function errors, and
  non-interference with constants and normal calls.

## References

- Haskell operator sections & backticks: https://wiki.haskell.org/Infix_operator
- Kotlin infix functions: https://kotlinlang.org/docs/functions.html#infix-notation
