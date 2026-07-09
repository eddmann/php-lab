#!/usr/bin/env bash
# Smoke tests for the #[Memoize] executable decorator. Run after scripts/build.sh.
set -uo pipefail

REPO_ROOT="$(cd "$(dirname "${BASH_SOURCE[0]}")/../.." && pwd)"
PHP="$REPO_ROOT/php-src/sapi/cli/php"

pass=0; fail=0
check() { # name | expected | actual
  if [ "$2" = "$3" ]; then echo "ok   - $1"; pass=$((pass+1));
  else echo "FAIL - $1 (expected [$2], got [$3])"; fail=$((fail+1)); fi
}

check "recursion is memoized (linear body calls)" "832040/31" \
  "$("$PHP" -r '$GLOBALS["c"]=0; #[Memoize] function fib(int $n):int { $GLOBALS["c"]++; return $n<2?$n:fib($n-1)+fib($n-2); } echo fib(30),"/",$GLOBALS["c"];')"

check "per-argument caching + correctness" "3,7,3" \
  "$("$PHP" -r '#[Memoize] function add(int $a,int $b):int { return $a+$b; } echo add(1,2),",",add(3,4),",",add(1,2);')"

check "default argument forwarded" "15,6" \
  "$("$PHP" -r '#[Memoize] function f(int $a,int $b=10):int { return $a+$b; } echo f(5),",",f(5,1);')"

check "named argument forwarded" "9" \
  "$("$PHP" -r '#[Memoize] function g(int $x,int $y):int { return $x-$y; } echo g(y:1,x:10);')"

check "non-memoized signature intact" "2" \
  "$("$PHP" -r 'function p(int $a,int $b=5):int { return $a+$b; } echo (new ReflectionFunction("p"))->getNumberOfParameters();')"

echo "----"
echo "passed: $pass, failed: $fail"
[ "$fail" -eq 0 ]
