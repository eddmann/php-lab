# PHP RFC: Refinements — scoped extension & override methods

- **Version:** 0.1
- **Date:** 2026-07-01
- **Author:** Edd Mann <the@eddmann.com>
- **Status:** Draft
- **Target:** PHP 8.5 (experimental fork)
- **Implementation:** [`feature.patch`](feature.patch)

## Introduction

PHP cannot put methods on scalars or arrays, and adding behaviour to a class you do
not own means subclassing or global helper functions. Borrowing from Ruby
refinements (and Smalltalk/Kotlin extension methods), this RFC adds `refinement` — a
way to add or override methods on any type, including scalars and arrays, that is
active only in files which opt in with `using`. It is the *safe* monkeypatch:
nothing leaks globally and libraries you call never see your changes.

## Proposal

```php
refinement Str for string {
    function shout(): string { return strtoupper($this) . '!'; }
}

using Str;
echo "hi"->shout();        // "HI!" — a method call on a scalar
```

- Refine **scalars & arrays** (`string`, `int`, `float`, `bool`, `array`), **add**
  methods to existing classes, or **override** existing methods — all scoped.
- Inside a refinement method, `$this` is the receiver.
- A refinement `for Base` also applies to subclasses.
- `using X;` activates a refinement for the **rest of the file**; resolution follows
  where a call is *written*, so a function keeps its refinements when called from
  elsewhere, and other files do not inherit them.

### Implementation

Each `refinement R for T { … }` method compiles to a plain function under a mangled,
NUL-prefixed key, with the receiver injected as the first parameter and `$this`
rewritten to it — so no engine `$this`-on-a-scalar surgery is needed. `using` records
the refinement in the per-file compile context. In a file with active refinements the
compiler rewrites `$recv->m(...)` to a runtime dispatcher (`zend_refinements.c`) that
looks up a matching active refinement (walking the class hierarchy for objects) or
falls back to a normal method call, restoring the caller's scope to preserve
visibility. Files that never say `using` compile and run exactly as before.

## Backward Incompatible Changes

- `refinement` and `using` are **semi-reserved** — still usable as method/constant
  names.
- In a `using` file, every `->` call routes through the dispatcher (an opt-in cost
  for that file only).

## Proposed PHP Version(s)

Next PHP 8.x minor.

## RFC Impact

- **Opcache:** the dispatcher call is emitted only inside opted-in files; other code
  is unchanged.
- **Scope (v1):** one target type per refinement; only `->` (not `?->`/`::`); no
  named arguments to refined calls.

## Future Scope

- Multiple target types per refinement; nullsafe and static-call support.

## Proposed Voting Choices

Yes/No, 2/3 majority.

## Patches and Tests

- [`feature.patch`](feature.patch); [`tests/`](tests/): `refinements` — scalar/array/
  class refinement, override, add, subclass application, scope isolation across files,
  and fallback to the normal method.

## References

- Ruby refinements; Kotlin/Smalltalk extension methods.
