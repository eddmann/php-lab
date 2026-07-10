#!/usr/bin/env bash
# Smoke tests for trailing closures (Swift trailing closures / Ruby blocks).
# Run after scripts/build.sh.
set -uo pipefail

REPO_ROOT="$(cd "$(dirname "${BASH_SOURCE[0]}")/../.." && pwd)"
PHP="$REPO_ROOT/php-src/sapi/cli/php"

pass=0; fail=0
check() { # name | expected | actual
  if [ "$2" = "$3" ]; then echo "ok   - $1"; pass=$((pass+1));
  else echo "FAIL - $1 (expected [$2], got [$3])"; fail=$((fail+1)); fi
}

check "zero-parameter block" "hi hi" \
  "$("$PHP" -r 'function twice(callable $f){ $f(); $f(); } twice() { echo "hi "; };' | sed 's/ $//')"

check "block with one parameter" "10,20,30" \
  "$("$PHP" -r 'function each_of(array $xs, callable $f){ foreach ($xs as $x) $f($x); }
                $out=[]; each_of([1,2,3]) { |$x| echo $x*10, ","; };' | sed 's/,$//')"

check "auto-capture by value" "HELLO" \
  "$("$PHP" -r 'function run(callable $f){ return $f(); } $g="hello"; echo run() { return strtoupper($g); };')"

check "usort comparator as block" "1,2,3" \
  "$("$PHP" -r '$x=[3,1,2]; usort($x) { |$a,$b| return $a <=> $b; }; echo implode(",", $x);')"

check "method-call receiver" "e1 e2" \
  "$("$PHP" -r 'class C { function __construct(private array $xs){}
                          function each(callable $f){ foreach ($this->xs as $x) $f($x); } }
                (new C([1,2]))->each() { |$x| echo "e$x "; };' | sed 's/ $//')"

check "static-call receiver" "s3 s4" \
  "$("$PHP" -r 'class C { static function of(array $xs, callable $f){ foreach ($xs as $x) $f($x); } }
                C::of([3,4]) { |$x| echo "s$x "; };' | sed 's/ $//')"

check "block-call is an expression" "14" \
  "$("$PHP" -r 'function apply(array $xs, callable $f){ return array_map($f, $xs); }
                echo array_sum(apply([1,2,3]) { |$x| return $x*$x; });')"

check "return exits the block only" "outer:inner" \
  "$("$PHP" -r 'function wrap(callable $f){ return $f(); }
                function outer(){ $r = wrap() { return "inner"; }; return "outer:$r"; }
                echo outer();')"

check "yield makes the block a generator" "1,2" \
  "$("$PHP" -r 'function collect(callable $f){ return iterator_to_array($f()); }
                echo implode(",", collect() { yield 1; yield 2; });')"

check "property hooks still parse" "10" \
  "$("$PHP" -r 'class P { public int $x = 5 { get => $this->x * 2; } } echo (new P)->x;')"

fcc_out="$("$PHP" -r 'strlen(...) { return 1; };' 2>&1 || true)"
check "FCC + block is a compile error" "rejected" \
  "$(case "$fcc_out" in *"first-class callable"*) echo rejected;; esac)"

check "plain closures and blocks unaffected" "fn X" \
  "$("$PHP" -r '$f = function(){ return "fn"; }; if (true) { echo $f(), " ", strtoupper("x"); }')"

echo "----"
echo "passed: $pass, failed: $fail"
[ "$fail" -eq 0 ]
