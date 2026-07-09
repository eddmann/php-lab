# PHP RFC: Built-in `Option`/`Result` types and the `?` propagation operator

- **Version:** 0.1
- **Date:** 2026-07-01
- **Author:** Edd Mann <the@eddmann.com>
- **Status:** Draft
- **Target:** PHP 8.5 (experimental fork)
- **Implementation:** [`feature.patch`](feature.patch)

## Introduction

PHP models absence and failure with `null`, exceptions, and sentinel returns, none
of which compose well. This RFC introduces two built-in algebraic types — `Option`
(`Some`/`None`) and `Result` (`Ok`/`Err`) — and a postfix `?` operator that threads
failure through a function without explicit branching, in the spirit of Rust. It
further makes the surrounding language (`??`, `match`, `instanceof`) aware of the
types and ships a combinator API, interop factories, and static-analysis stubs.

## Proposal

### The types

`Option` and `Result` are built-in engine classes (always available, no autoload),
both implementing a shared `Propagatable` interface (`isFailure()` / `getValue()`).

```php
Some(42); None(); Ok($v); Err($e);   // global constructors
```

### The `?` propagation operator

A postfix `?` short-circuits the enclosing function: if the operand is a failure
(`None`/`Err`) the function returns it immediately (running `finally` and the
declared return-type check, like a normal `return`); otherwise it unwraps to the
contained value.

```php
function firstName(int $id): Option {
    $user = findUser($id)?;   // returns None from firstName() on failure
    return Some($user->name);
}
```

### Language integration

```php
$name = findName($id) ?? "anonymous";        // ?? unwraps Some/Ok, falls back on None/Err
$m = match ($r) { Ok($v) => $v, Err($e) => -1 };   // match destructuring
if ($r instanceof Ok($v)) { use_it($v); }    // if-let style binding
```

### Combinators, factories, interop

`map`, `flatMap`, `filter`, `mapErr`, `orElse`, `unwrap`, `expect`, `unwrapOr`,
`okOr`, `toOption`, `toNullable`, plus `Result::try()`, `Option::fromNullable()`,
`Option::all()`/`Result::all()`, and `__toString()`.

### Static analysis

`option-result.phpstub` supplies `@template` generic shapes (`Option<T>`,
`Result<T,E>`) so PHPStan/Psalm infer and narrow through `map`/`?`/`instanceof`,
while the runtime stays dynamic.

### Implementation

New `ZEND_AST_PROPAGATE` node; the `?` grammar rule is given deliberately low
precedence so the ternary always wins any collision (bison `%expect 0` preserved).
`??`/`match`/`instanceof` gain dedicated compile paths that only engage for these
types, leaving existing code byte-for-byte unchanged. The types and their methods
are implemented in C in a dedicated engine file.

## Backward Incompatible Changes

- `Some`, `None`, `Ok`, `Err`, `Option`, `Result`, `Propagatable` become global
  classes and shadow userland classes of the same name.
- `$x = f()? + 1;` parses as a ternary; write `(f()?) + 1`.

## Proposed PHP Version(s)

Next PHP 8.x minor.

## RFC Impact

- **Opcache:** the `?` operator emits standard opcodes modelled on
  null-coalescing-assignment; no new opcode.
- **Reflection:** the new classes are reflectable like other internal classes.
- **`??=`** is intentionally left unchanged (read form only).

## Future Scope

- Compile-time exhaustiveness checking for `match` over `Option`/`Result`.
- A typed error channel via real runtime generics.

## Proposed Voting Choices

Yes/No, 2/3 majority.

## Patches and Tests

- [`feature.patch`](feature.patch); suite in [`tests/`](tests/): `propagate`,
  `propagate_return_type`, `coalesce_option`, `match_patterns`, `instanceof_bind`,
  `option_result_combinators`, `option_result_factories`.

## References

- Rust `Option`/`Result` and the `?` operator: https://doc.rust-lang.org/
