# 14 — Uniform Function Call Syntax (UFCS)

*Borrowed from D and Nim.*

When `$obj->foo(...)` names **no method** on the object's class (and there is no
`__call`), and a **free function** `foo` exists, the call becomes `foo($obj, ...)`
— the receiver slides in as the first argument. Any plain function can be read as
a method, so utilities compose into fluent chains:

```php
final class Vec {
    public function __construct(public int $x, public int $y) {}
}
function len(Vec $v): float   { return sqrt($v->x ** 2 + $v->y ** 2); }
function scale(Vec $v, int $k): Vec { return new Vec($v->x * $k, $v->y * $k); }

$v = new Vec(3, 4);
$v->len();          // => len($v)          == 5.0
$v->scale(2);       // => scale($v, 2)     == Vec(6, 8)
$v->scale(2)->len(); // chains: len(scale($v, 2))
```

`add($a, $b)` reads as `$a->add($b)`; a whole module of free functions over a type
becomes a method-style API without touching the class.

## Resolution rules

The fallback fires **only** on the "method genuinely not found" path, so nothing
about normal dispatch changes:

1. A **real method** (including inherited ones) always wins.
2. `__call` / `__callStatic` always wins over UFCS.
3. Otherwise, if a function with that name exists, the call is
   `function($receiver, ...args)`.
4. If no such function exists either, you get the usual
   `Call to undefined method` error.

```php
class Base { public function kind() { return "base"; } }
class Derived extends Base {}
function kind($o) { return "free"; }
(new Derived)->kind();   // "base" — the inherited method wins
```

The receiver is passed **by value** as the first positional argument; the call's
own positional and spread (`...$args`) arguments follow. Chaining, exception
propagation, and nested UFCS (a free function whose body itself uses `->`) all
work.

## Scope of this experiment (v1)

- **Object receivers only.** `$string->trim()` / `$array->count()` are *not*
  rewritten: scalars and arrays never reach the object method-call path, so
  supporting them needs engine work one level deeper (intercepting the
  "method call on a non-object" branch of the VM). That is deliberately left as
  future scope to keep this a small, self-contained patch.
- **The free function is resolved in the global namespace.** Method dispatch only
  sees the bare name, so `$obj->foo()` looks up `\foo()`. Global helpers (and
  much of the standard library) resolve fine even from a namespaced call site;
  a namespaced `App\foo()` would need to be aliased/defined globally.
- The receiver is passed by value; by-reference parameters on the target and
  named arguments are not forwarded in v1.

## How it works

No new tokens, no new opcodes, no scanner/parser/VM changes. Method resolution
already funnels through `zend_std_get_method`. On the branch where the method is
absent and there is no `__call`, we look the name up in the global function table
and, if found, hand back an **internal-function trampoline** (the same mechanism
PHP uses for property-hook trampolines). Its handler prepends the receiver and
calls the free function, then frees itself. Ordinary method calls never allocate
the trampoline, so they pay nothing. See [`feature.patch`](feature.patch) and the
[RFC](RFC.md).

## Try it

```bash
scripts/setup.sh 14-ufcs && scripts/build.sh
features/14-ufcs/smoke.sh
```
