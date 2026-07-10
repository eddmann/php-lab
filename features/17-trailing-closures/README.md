# 17 — Trailing closures

*Borrowed from Swift's trailing closures and Ruby's blocks.*

When a call's last argument is a closure, write it as a block **after** the
parentheses — with optional Ruby-style `|…|` parameters:

```php
usort($xs) { |$a, $b| return $a <=> $b; };

each_of($users) { |$u| notify($u); };

$total = fold($xs, 0) { |$acc, $x| return $acc + $x; };

twice() { echo "hi\n"; };            // zero-parameter block
```

The block is appended to the argument list as the call's final argument, so any
function whose *last* parameter is a callable — userland or internal — gets the
DSL look for free.

## Behaviour

- **Auto-capture.** The block captures surrounding variables automatically,
  **by value** (it desugars to the arrow-function machinery):

  ```php
  $greeting = "hello";
  run() { return strtoupper($greeting); };   // sees $greeting
  ```

- **`return` returns from the block** (Swift semantics, not Ruby's non-local
  return); `yield` inside a block makes the block a generator.
- Works after plain calls, method calls (`$c->each() { … }`), nullsafe calls,
  and static calls (`C::of($xs) { … }`).
- The whole thing is an expression: `array_sum(apply($xs) { |$x| return $x * $x; })`.
- As with any expression statement, a statement-level block-call ends with `;`
  after the closing brace.

## Non-interference

- **Property hooks** — the one other place `{` legally follows an expression
  (`public int $x = 5 { get => …; }`) — parse exactly as before: the grammar
  prefers the trailing-block reading only for *call* expressions, and a function
  call was never a valid property default. The full upstream property-hook suite
  passes.
- First-class callables reject a block: `strlen(...) { … }` is a compile error.
- Ordinary `{ }` statement blocks, closures, and `match` are untouched.

## How it works

Grammar sugar plus one small compiler tweak:

1. Every call production gains an `optional_trailing_block`; the block parses as
   `'{' |params| statements '}'` and is appended to the argument list.
2. The block is built as a `ZEND_AST_ARROW_FUNC` whose body is a **statement
   list** — a one-condition change in `zend_compile_func_decl_ex` compiles such
   bodies like a closure body instead of wrapping them in an implicit `return`,
   while the existing implicit-binds pass provides the auto-capture.
3. The block does the same `backup_fn_flags` bookkeeping as `fn`/`function`, so
   a `yield` inside the block marks the *block* (not the enclosing function) as
   a generator.

No new opcodes; no VM changes. See [`feature.patch`](feature.patch) and the
[RFC](RFC.md).

## Try it

```bash
scripts/setup.sh 17-trailing-closures && scripts/build.sh
features/17-trailing-closures/smoke.sh
```
