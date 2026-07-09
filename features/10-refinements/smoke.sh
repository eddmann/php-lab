#!/usr/bin/env bash
# Smoke tests for refinements (scoped extension/override methods).
# Run after scripts/build.sh.
set -uo pipefail

REPO_ROOT="$(cd "$(dirname "${BASH_SOURCE[0]}")/../.." && pwd)"
PHP="$REPO_ROOT/php-src/sapi/cli/php"

pass=0; fail=0
check() { # name | expected | actual
  if [ "$2" = "$3" ]; then echo "ok   - $1"; pass=$((pass+1));
  else echo "FAIL - $1 (expected [$2], got [$3])"; fail=$((fail+1)); fi
}

check "scalar extension method" "HI!" \
  "$("$PHP" -r 'refinement S for string { function shout(): string { return strtoupper($this)."!"; } } using S; echo "hi"->shout();')"

check "int method with arg" "42" \
  "$("$PHP" -r 'refinement N for int { function plus(int $n): int { return $this+$n; } } using N; echo (40)->plus(2);')"

check "array fluent method" "10" \
  "$("$PHP" -r 'refinement A for array { function total(): int { return array_sum($this); } } using A; echo [1,2,3,4]->total();')"

check "add method to class" '$10.50' \
  "$("$PHP" -r 'class M { function __construct(public int $c){} } refinement F for M { function d(): string { return "$".number_format($this->c/100,2); } } using F; echo (new M(1050))->d();')"

check "override class method" "10 EUR" \
  "$("$PHP" -r 'class M { function __construct(public int $c){} function f(): string { return "\$".$this->c; } } refinement E for M { function f(): string { return $this->c." EUR"; } } using E; echo (new M(10))->f();')"

check "normal call falls through" "hello" \
  "$("$PHP" -r 'refinement S for string { function x(): string { return $this; } } using S; class B { function hi(): string { return "hello"; } } echo (new B)->hi();')"

check "private method visibility preserved" "shh" \
  "$("$PHP" -r 'refinement S for string { function x(): string { return $this; } } using S; class A { private function s(): string { return "shh"; } function run(): string { return $this->s(); } } echo (new A)->run();')"

check "subclass inherits refinement for base" "B!" \
  "$("$PHP" -r 'class Base {} class Sub extends Base {} refinement R for Base { function tag(): string { return "B!"; } } using R; echo (new Sub)->tag();')"

check "using/refinement usable as method names" "u1r" \
  "$("$PHP" -r 'class C { function using($x){return "u$x";} function refinement(){return "r";} } echo (new C)->using(1),(new C)->refinement();')"

echo "----"
echo "passed: $pass, failed: $fail"
[ "$fail" -eq 0 ]
