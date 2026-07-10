#!/usr/bin/env bash
# Smoke tests for chained comparisons (Python-style `a < b < c`).
# Run after scripts/build.sh.
set -uo pipefail

REPO_ROOT="$(cd "$(dirname "${BASH_SOURCE[0]}")/../.." && pwd)"
PHP="$REPO_ROOT/php-src/sapi/cli/php"

pass=0; fail=0
check() { # name | expected | actual
  if [ "$2" = "$3" ]; then echo "ok   - $1"; pass=$((pass+1));
  else echo "FAIL - $1 (expected [$2], got [$3])"; fail=$((fail+1)); fi
}

check "ascending chain is true" "1" \
  "$("$PHP" -r 'var_export(1 < 2 < 3 === true ? 1 : 0);')"

check "range idiom in-range" "1" \
  "$("$PHP" -r '$i=5; echo 0 <= $i < 10 ? 1 : 0;')"

check "range idiom out-of-range" "0" \
  "$("$PHP" -r '$i=15; echo 0 <= $i < 10 ? 1 : 0;')"

check "failing middle link is false" "0" \
  "$("$PHP" -r 'echo 1 < 5 < 3 ? 1 : 0;')"

check "descending with > and >=" "1" \
  "$("$PHP" -r '$n=7; echo 10 >= $n > 0 ? 1 : 0;')"

check "mixed relational operators" "1" \
  "$("$PHP" -r 'echo 5 > 3 < 4 ? 1 : 0;')"

check "middle operand evaluated once (prints mid once)" "mid1" \
  "$("$PHP" -r 'function mid(){ echo "mid"; return 5; } echo (0 < mid() < 10 ? 1 : 0);')"

check "short-circuit skips the tail" "0" \
  "$("$PHP" -r 'function r(){ echo "r"; return 100; } echo 5 < 2 < r() ? 1 : 0;')"

check "four-operand chain" "1,0" \
  "$("$PHP" -r 'echo (1<2<3<4?1:0), ",", (1<2<9<4?1:0);')"

check "mixed precedence preserved: (1<2)==true" "1" \
  "$("$PHP" -r 'echo (1 < 2 == true) ? 1 : 0;')"

eq_out="$("$PHP" -r 'var_dump(1 == 1 == 1);' 2>&1 || true)"
check "equality chaining is still rejected (parse error)" "rejected" \
  "$(case "$eq_out" in *"syntax error"*) echo rejected;; esac)"

echo "----"
echo "passed: $pass, failed: $fail"
[ "$fail" -eq 0 ]
