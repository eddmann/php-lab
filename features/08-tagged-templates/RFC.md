# PHP RFC: Tagged template strings

- **Version:** 0.1
- **Date:** 2026-07-01
- **Author:** Edd Mann <the@eddmann.com>
- **Status:** Draft
- **Target:** PHP 8.5 (experimental fork)
- **Implementation:** [`feature.patch`](feature.patch)

## Introduction

PHP string interpolation always produces a finished string, so a function can never
see where the interpolation holes were or treat their values specially. Borrowing
from JavaScript tagged templates, this RFC lets a function name prefix an
interpolated string and receive the constant pieces and the interpolated values
**separately** — the primitive behind safe query/markup builders, i18n, and custom
escaping.

## Proposal

```php
$id = 5; $name = "O'Brien";
echo sql"SELECT * FROM users WHERE id = {$id} AND name = {$name}";
// SELECT * FROM users WHERE id = 5 AND name = 'O''Brien'
```

`tag"a {$x} b"` calls `tag(['a ', ' b'], [$x])`. A tag is any function
`tag(array $strings, array $values): mixed` where
`count($strings) === count($values) + 1` (JS-style); empty `""` pieces are inserted
where interpolations are adjacent or at the ends, so the arrays interleave cleanly.

A built-in demo tag, **`sql`**, ships as an example: it interleaves the parts with
SQL-escaped values (strings single-quoted with `''` escaping, numbers inline, `null`
→ `NULL`). It demonstrates the mechanism and is not a replacement for parameterised
queries.

### Implementation

The syntax is **pure parser sugar** — two `expr` grammar rules for the interpolated
and no-interpolation forms — that splits PHP's existing `ZEND_AST_ENCAPS_LIST` into a
`strings` array and a `values` array and builds `CALL(tag, [strings, values])`. The
demo `sql` handler is a runtime function implemented in its own engine file
(`zend_tagged.c`), so the feature is independent of any other.

## Backward Incompatible Changes

- A bareword immediately followed by a string literal (`tag"..."`) is now a
  tagged-template call. This only affects the previously-**invalid**
  `BAREWORD "string"` adjacency, so there is no real break.

## Proposed PHP Version(s)

Next PHP 8.x minor.

## RFC Impact

- **Opcache:** compiles to an ordinary function call; no new opcode.

## Future Scope

- A userland registry of standard tags (html, url, i18n); nowdoc/heredoc tags.

## Proposed Voting Choices

Yes/No, 2/3 majority.

## Patches and Tests

- [`feature.patch`](feature.patch); [`tests/`](tests/): `tagged_template` — parts/
  values interleaving, escaping, adjacent and edge interpolations, no-interpolation
  form, and the `count(strings) === count(values) + 1` invariant.

## References

- JavaScript tagged templates: https://developer.mozilla.org/
