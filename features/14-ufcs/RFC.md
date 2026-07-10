# PHP RFC: Uniform Function Call Syntax (UFCS)

- **Version:** 0.1
- **Date:** 2026-07-09
- **Author:** Edd Mann <the@eddmann.com>
- **Status:** Draft
- **Target:** PHP 8.5 (experimental fork)
- **Implementation:** [`feature.patch`](feature.patch)

## Introduction

PHP splits its API surface in two: methods are called `$obj->m($x)`, free
functions are called `f($obj, $x)`. Standard-library helpers and userland utility
functions live on the "free function" side and cannot participate in the fluent,
left-to-right chains that method syntax allows. Borrowing Uniform Function Call
Syntax from D and Nim, this RFC lets a free function be **called in method
position**, with the receiver passed as its first argument.

## Proposal

When `$obj->foo(args)` resolves to no method on `$obj`'s class and the class has no
`__call`, and a function `foo` exists, the call is evaluated as `foo($obj, args)`:

```php
function len(Vec $v): float          { /* ... */ }
function scale(Vec $v, int $k): Vec  { /* ... */ }

$v->len();            // len($v)
$v->scale(2)->len();  // len(scale($v, 2))
```

### Resolution order

1. A real method (own or inherited) wins.
2. `__call` / `__callStatic` wins.
3. Otherwise a function of that name, called as `foo($receiver, ...args)`.
4. Otherwise the existing `Call to undefined method` `Error`.

Method dispatch is unchanged for every case that resolves today; UFCS only occupies
the slot that previously went straight to an error.

### Semantics

- The receiver is passed **by value** as the first positional argument; the call's
  positional and spread arguments follow it.
- Chaining, exception propagation, and reentrant UFCS (a target whose body uses
  `->` again) behave as expected.
- The free function is resolved in the **global namespace** — method dispatch only
  carries the bare name.

## Backward Incompatible Changes

None for currently-valid programs. Today `$obj->foo()` with no matching
method/`__call` is always a fatal `Error`; UFCS gives some of those calls a
meaning instead. No existing successful call changes behaviour.

## Scope / Future Scope

- **Object receivers only in v1.** Scalar and array receivers
  (`$string->trim()`) never enter the object method-call path; supporting them
  requires intercepting the "method call on non-object" branch of the VM and is
  left as future work so this patch stays small and self-contained.
- By-reference target parameters and named-argument forwarding.
- Namespace-aware resolution (resolving the target relative to the call site's
  namespace, with the usual global fallback).

## RFC Impact

- **Opcache:** none. No new opcode; the fallback builds a transient
  internal-function trampoline only on the method-not-found slow path, so cached
  method call sites are unaffected.
- **Tokenizer / grammar / VM:** unchanged. The change is confined to
  `zend_std_get_method` plus a small trampoline helper in
  `zend_object_handlers.c`.

## Proposed PHP Version(s)

Next PHP 8.x minor.

## Proposed Voting Choices

Yes/No, 2/3 majority.

## Patches and Tests

- [`feature.patch`](feature.patch); [`tests/`](tests/): receiver-first calls,
  extra/spread arguments, chaining, method-and-`__call` precedence, inheritance,
  reentrancy, exception propagation, global resolution from a namespaced call
  site, and the undefined-method error when no function exists.

## References

- D UFCS: https://dlang.org/spec/function.html#pseudo-member
- Nim method call syntax: https://nim-lang.org/docs/manual.html#procedures-method-call-syntax
