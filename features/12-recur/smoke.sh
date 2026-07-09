#!/usr/bin/env bash
# Smoke tests for `recur` + automatic self-TCO. Run after scripts/build.sh.
set -uo pipefail

REPO_ROOT="$(cd "$(dirname "${BASH_SOURCE[0]}")/../.." && pwd)"
PHP="$REPO_ROOT/php-src/sapi/cli/php"

pass=0; fail=0
check() { # name | expected | actual
  if [ "$2" = "$3" ]; then
    echo "ok   - $1"; pass=$((pass+1))
  else
    echo "FAIL - $1 (expected [$2], got [$3])"; fail=$((fail+1))
  fi
}

check "explicit recur at depth 1M (constant stack)" "500000500000" \
  "$("$PHP" -r 'function s(int $n, int $a) { if ($n === 0) return $a; return recur($n - 1, $a + $n); } echo s(1000000, 0);')"

check "bare-statement recur" "liftoff" \
  "$("$PHP" -r 'function c(int $n) { if ($n === 0) { echo "liftoff"; return; } recur($n - 1); } c(100000);')"

check "swap safety" "2,1" \
  "$("$PHP" -r 'function w(int $a, int $b, int $n) { if ($n === 0) return "$a,$b"; return recur($b, $a, $n - 1); } echo w(1, 2, 5);')"

check "auto-TCO: plain self tail call at depth 1M" "500000499999" \
  "$("$PHP" -r 'function f(int $n, int $a) { if ($n <= 1) return $a; return f($n - 1, $a + $n); } echo f(1000000, 0);')"

check "auto-TCO preserves method dispatch" "B" \
  "$("$PHP" -r 'class A { function f(int $n): string { if ($n === 0) return "A"; return $this->f($n - 1); } } class B extends A { function f(int $n): string { return "B"; } } echo (new B)->f(3);')"

check "non-tail recursion untouched (fib)" "6765" \
  "$("$PHP" -r 'function fib(int $n): int { return $n < 2 ? $n : fib($n-1) + fib($n-2); } echo fib(20);')"

check "tail-position error" "yes" \
  "$("$PHP" -r 'function f(int $n) { $x = recur($n); }' 2>&1 | grep -q "tail position" && echo yes)"

echo
echo "passed: $pass, failed: $fail"
exit "$fail"
