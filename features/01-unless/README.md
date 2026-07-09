# `unless` — Ruby-style negated `if`

> **Inspiration:** Ruby · **Layer:** front-end only (lexer + parser) · **Commit:** `e2dfce0`

## What it is

`unless` is sugar for a negated `if`. It runs its body when the condition is
**false**. Three forms are supported:

```php
unless ($user->isBanned()) {                 // block form
    echo "welcome";
}

unless ($cache->has($key)) {                 // with else
    $value = compute();
} else {
    $value = $cache->get($key);
}

print "saved\n" unless $dryRun;              // statement modifier
```

`unless (C) { S }` is exactly equivalent to `if (!C) { S }`.

## Why

It's the simplest possible language change — no new types, no runtime, no engine
work — which made it the ideal warm-up for wiring up the whole build/patch/test
loop. It also reads well for guard-style early conditions where `if (!…)` is noisy.

## How it works

Entirely in the **front-end**; the compiler and runtime never learn about it.

1. **Lexer** (`Zend/zend_language_scanner.l`): a new keyword token `T_UNLESS`.
2. **Parser** (`Zend/zend_language_parser.y`): the `unless` rules desugar to the
   *same* AST that `if` produces — `ZEND_AST_IF` / `ZEND_AST_IF_ELEM` — with the
   condition wrapped in a boolean-NOT node (`!C`). Because it lowers to the
   existing `if` AST, there is zero new compiler or opcode surface.

`unless` is **semi-reserved**: it's added to `reserved_non_modifiers`, so it
remains usable as a method or constant name (`$obj->unless()`), preserving
backward compatibility.

## Caveats

- None of note — it desugars to `if`, so semantics, scoping, and short-circuiting
  are identical.

## Tests

- `smoke.sh` — smoke tests (block, else, modifier, semi-reserved usage)
- `tests/unless.phpt`, `tests/unless_comprehensive.phpt` — in-tree regression tests
