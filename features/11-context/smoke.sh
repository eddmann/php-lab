#!/usr/bin/env bash
# Smoke tests for context parameters / `provide` (Scala given/using-style implicits).
# Run after scripts/build.sh.
set -uo pipefail

REPO_ROOT="$(cd "$(dirname "${BASH_SOURCE[0]}")/../.." && pwd)"
PHP="$REPO_ROOT/php-src/sapi/cli/php"

pass=0; fail=0
check() { # name | expected | actual
  if [ "$2" = "$3" ]; then echo "ok   - $1"; pass=$((pass+1));
  else echo "FAIL - $1 (expected [$2], got [$3])"; fail=$((fail+1)); fi
}

check "block provide + context param auto-supplied" "42" \
  "$("$PHP" -r 'class Clock { function __construct(public int $t){} function time(): int { return $this->t; } } function now(context Clock $c): int { return $c->time(); } provide new Clock(42) { echo now(); }')"

check "deep propagation (3 calls down)" "deep" \
  "$("$PHP" -r 'class L { function log(string $m): void { echo $m; } } function a(){ b(); } function b(){ c(); } function c(context L $l){ $l->log("deep"); } provide new L() { a(); }')"

check "statement form active for rest of function" "abc" \
  "$("$PHP" -r 'class R { function __construct(public string $id){} } function h(){ provide new R("abc"); inner(); } function inner(context R $r){ echo $r->id; } h();')"

check "nearest-wins nested shadowing" "outer/inner/outer" \
  "$("$PHP" -r 'class C { function __construct(public string $v){} } function s(context C $c){ echo $c->v; } provide new C("outer") { s(); echo "/"; provide new C("inner") { s(); } echo "/"; s(); }')"

check "value popped on exception (no leak)" "caught:gone" \
  "$("$PHP" -r 'class C {} function need(context C $c){} try { provide new C() { throw new Exception("b"); } } catch (Exception $e) { echo "caught"; } try { need(); } catch (\Error $e) { echo ":gone"; }')"

check "missing value throws clear error" "No context value of type C is available" \
  "$("$PHP" -r 'class C {} function need(context C $c){} try { need(); } catch (\Error $e) { echo $e->getMessage(); }')"

check "default used when nothing provided" "fallback" \
  "$("$PHP" -r 'class C { function __construct(public string $v){} } function f(context C $c = new C("fallback")): string { return $c->v; } echo f();')"

check "interface match" "7" \
  "$("$PHP" -r 'interface Clock { function time(): int; } class Sys implements Clock { function time(): int { return 7; } } function now(context Clock $c): int { return $c->time(); } provide new Sys() { echo now(); }')"

check "context param mixes with normal + named args" "103" \
  "$("$PHP" -r 'class C { function __construct(public int $b){} } function add(int $x, context C $c, int $y = 10): int { return $x+$c->b+$y; } provide new C(100) { echo add(1, y: 2); }')"

check "context/provide usable as method names" "m1p" \
  "$("$PHP" -r 'class H { function context($x){return "m$x";} function provide(){return "p";} } echo (new H)->context(1),(new H)->provide();')"

check "class Context still parses (case-sensitive keyword)" "314" \
  "$("$PHP" -r 'class Context { public int $x = 314; } echo (new Context)->x;')"

echo "----"
echo "passed: $pass, failed: $fail"
[ "$fail" -eq 0 ]
