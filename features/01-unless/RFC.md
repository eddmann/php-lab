# PHP RFC: `unless` statement

- **Version:** 0.1
- **Date:** 2026-07-01
- **Author:** Edd Mann <the@eddmann.com>
- **Status:** Draft
- **Target:** PHP 8.5 (experimental fork)
- **Implementation:** [`feature.patch`](feature.patch)

## Introduction

Guard-style early conditions are common in PHP, but they read awkwardly when the
condition must be negated: `if (!$user->isActive()) { … }`. Borrowing from Ruby,
this RFC introduces `unless` — an inverted `if` that runs its body when the
condition is **false**. It is a small, self-contained readability construct with no
new runtime semantics.

## Proposal

`unless` is supported in three forms, each exactly equivalent to the corresponding
negated `if`:

```php
// Block form
unless ($user->isBanned()) {
    echo "welcome";
}

// With else
unless ($cache->has($key)) {
    $value = compute();
} else {
    $value = $cache->get($key);
}

// Statement modifier
print "saved\n" unless $dryRun;
```

`unless (C) { S }` is defined to behave identically to `if (!C) { S }`, including
short-circuiting, scoping, and truthiness rules.

### Grammar & implementation

The change is confined to the **front-end**. A new keyword token `T_UNLESS` is added
to the lexer, and parser rules desugar `unless` to the existing `ZEND_AST_IF` /
`ZEND_AST_IF_ELEM` AST with the condition wrapped in a boolean-NOT node. The
compiler and VM are untouched — there is no new opcode surface.

`unless` is added to `reserved_non_modifiers`, making it **semi-reserved**: it
remains usable as a method or constant name (`$obj->unless()`, `C::unless`).

## Backward Incompatible Changes

None for typical code. `unless` becomes a reserved keyword in statement position; a
global `function unless()` or a class named `unless` would no longer parse, but
method/constant use is preserved.

## Proposed PHP Version(s)

Next PHP 8.x minor.

## RFC Impact

- **SAPIs / Opcache:** none — desugars to existing `if` AST.
- **Tokenizer:** adds `T_UNLESS`; `token_get_all()` / `token_name()` report it.
- **Reflection:** none.

## Future Scope

- `until` as the loop counterpart to `unless` (inverted `while`).

## Proposed Voting Choices

Yes/No, requiring a 2/3 majority.

## Patches and Tests

- Implementation and tests: [`feature.patch`](feature.patch).
- Test suite: [`tests/`](tests/) — block, `else`, modifier, nesting, truthiness,
  and semi-reserved usage; `Zend/tests/unless.phpt` mirrors this in-tree.

## References

- Ruby `unless`: https://docs.ruby-lang.org/
