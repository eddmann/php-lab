# Tagged template strings

> **Inspiration:** JavaScript tagged templates · **Layer:** parser only

## What it is

Prefix a double-quoted (interpolated) string with a function name and that function
receives the **constant pieces** and the **interpolated values** separately —
instead of a single pre-built string. This is the basis for safe query/markup
builders, i18n, custom escaping, DSLs, etc.

```php
$id = 5;
$name = "O'Brien";
echo sql"SELECT * FROM users WHERE id = {$id} AND name = {$name}";
// SELECT * FROM users WHERE id = 5 AND name = 'O''Brien'
```

`tag"a {$x} b"` calls `tag(['a ', ' b'], [$x])`. The handler decides how to combine
the parts — so values can be escaped, parameterised, or transformed rather than
blindly concatenated.

## The handler contract

A tag is any function `tag(array $strings, array $values): mixed`:

- `$strings` are the literal pieces; `$values` are the interpolated expressions.
- **`count($strings) === count($values) + 1`** (JS-style). Empty `""` pieces are
  inserted where interpolations are adjacent or sit at the very start/end, so the
  two arrays always interleave cleanly: `strings[0] . values[0] . strings[1] . …`.

```php
tag"plain"      // tag(["plain"], [])
tag"{$a}{$b}"   // tag(["", "", ""], [$a, $b])
tag"x{$a}"      // tag(["x", ""], [$a])
```

A built-in demo tag ships: **`sql`** interleaves the parts with SQL-escaped values
(strings single-quoted with `''` escaping, numbers inline, `null` → `NULL`). It
demonstrates the mechanism — not a replacement for real parameterised queries.

## Why

PHP string interpolation always produces a finished string, so a function can't see
where the holes were or escape them differently. Tagged templates hand the function
the structure, which is exactly what safe builders need. It's a small, general
primitive that a lot of libraries can build on.

## How it works

**Pure parser sugar — no AST node, compiler, or runtime changes.** Two `expr`
grammar rules:

- `T_STRING '"' encaps_list '"'` — interpolated form. PHP already parses the body
  into a `ZEND_AST_ENCAPS_LIST` whose children alternate constant-string `ZEND_AST_ZVAL`
  pieces and value expressions.
- `T_STRING T_CONSTANT_ENCAPSED_STRING` — the no-interpolation form.

A helper (`zend_ast_create_tagged_template` in `zend_ast.c`) splits the encaps
children into a `strings` array (the constant pieces, padded with `""`) and a
`values` array (the expressions), then builds `CALL(tag, [strings, values])`. The
`%expect 0` grammar-conflict gate still holds.

## Caveats / BC

- A bareword immediately followed by a string literal (`tag"..."` or
  `tag "..."` — whitespace is fine) is now a tagged-template call. This only affects
  the previously-**invalid** `BAREWORD "string"` adjacency, so there's no real BC
  break.
- Interpolations use ordinary double-quote rules (`{$x}`, `$x`, `$obj->p`, `$a[0]`).
- The tag name resolves like any function call (it's `ZEND_NAME_FQ`).

## Tests

- `smoke.sh` — smoke tests
- `tests/tagged_template.phpt`
