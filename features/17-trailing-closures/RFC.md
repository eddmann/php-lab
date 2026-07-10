# PHP RFC: Trailing closures

- **Version:** 0.1
- **Date:** 2026-07-09
- **Author:** Edd Mann <the@eddmann.com>
- **Status:** Draft
- **Target:** PHP 8.5 (experimental fork)
- **Implementation:** [`feature.patch`](feature.patch)

## Introduction

Callback-taking APIs are everywhere in PHP, but the callback is buried inside
the parentheses: `usort($xs, function ($a, $b) { return $a <=> $b; });`.
Swift's trailing closures and Ruby's blocks solve the readability problem by
letting the closure follow the call. This RFC adds that form to PHP.

## Proposal

If a `{ … }` block follows a call's argument list, it becomes the call's final
argument — a closure. Parameters use Ruby-style pipes:

```php
usort($xs) { |$a, $b| return $a <=> $b; };
each_of($users) { |$u| notify($u); };
twice() { echo "hi\n"; };                     // no parameters
$t = fold($xs, 0) { |$acc, $x| return $acc + $x; };
```

### Semantics

- The block desugars to an **auto-capturing closure** (arrow-function
  machinery, statement body): surrounding variables are imported **by value**,
  `$this` is available as usual.
- `return` exits the **block**; `yield` makes the block a generator (the
  enclosing function's flags are unaffected).
- Supported after plain, method, nullsafe-method and static calls; the result
  is an ordinary expression.
- First-class callable syntax (`f(...)`) cannot take a block — compile error.

## Backward Incompatible Changes

`call(...) { … }` is a syntax error in stock PHP, so no valid program changes
meaning. The one grammar overlap is **hooked properties**, where an expression
may legitimately be followed by `{`: `public int $x = EXPR { get => …; }`. The
grammar resolves `CALL(…) {` in favour of the trailing block — safe, because a
function call is never a valid property default (constant expressions only).
All upstream property-hook tests pass unchanged. One upstream test that asserts
the exact *order* of tokens in a parse-error "expecting" list is updated.

## RFC Impact

- **Opcache / VM:** none — the block compiles to a regular closure; no new
  opcode.
- **Tokenizer:** no new token (the `|` pipes reuse existing tokens).
- **Grammar:** call productions gain an `optional_trailing_block`; two
  precedence symbols resolve the hooked-property overlap. Conflict-free
  (`%expect 0` holds).
- **Compiler:** `ZEND_AST_ARROW_FUNC` additionally accepts a statement-list
  body (previously only expressions).

## Open Questions / Future Scope

- By-reference capture (`|&$acc|`) and parameter types/defaults in the pipes.
- Ruby-style paren-less calls (`transaction { … }`).
- Passing the block to a non-final parameter position.

## Proposed Voting Choices

Yes/No, 2/3 majority.

## Patches and Tests

- [`feature.patch`](feature.patch); [`tests/`](tests/): zero/one/two-parameter
  blocks, auto-capture, internal callbacks (`usort`), method/static receivers,
  expression position, multi-statement bodies, nesting, `return` scoping,
  generators-in-blocks, property-hook coexistence, and the FCC guard.

## References

- Swift trailing closures: https://docs.swift.org/swift-book/documentation/the-swift-programming-language/closures/#Trailing-Closures
- Ruby blocks: https://docs.ruby-lang.org/en/master/syntax/methods_rdoc.html#label-Block+Argument
