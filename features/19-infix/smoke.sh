#!/usr/bin/env bash
# Smoke tests for infix function calls (Haskell backticks / Kotlin infix).
# Run after scripts/build.sh.
set -uo pipefail

REPO_ROOT="$(cd "$(dirname "${BASH_SOURCE[0]}")/../.." && pwd)"
PHP="$REPO_ROOT/php-src/sapi/cli/php"

pass=0; fail=0
check() { # name | expected | actual
  if [ "$2" = "$3" ]; then echo "ok   - $1"; pass=$((pass+1));
  else echo "FAIL - $1 (expected [$2], got [$3])"; fail=$((fail+1)); fi
}

check "internal function as infix (max)" "7" \
  "$("$PHP" -r 'echo 3 max 7;')"

check "internal function as infix (pow)" "1024" \
  "$("$PHP" -r 'echo 2 pow 10;')"

check "str_contains reads naturally" "yes" \
  "$("$PHP" -r 'echo "hello" str_contains "ell" ? "yes" : "no";')"

check "user function infix" "6" \
  "$("$PHP" -r 'function add($a,$b){ return $a+$b; } echo 2 add 4;')"

check "left-associative chaining" "6" \
  "$("$PHP" -r 'function add($a,$b){ return $a+$b; } echo 1 add 2 add 3;')"

check "array literal as operand (clamp idiom)" "100,0,42" \
  "$("$PHP" -r 'function clamp(int $x, array $r){ return max($r[0], min($r[1], $x)); }
                echo 150 clamp [0,100], ",", -5 clamp [0,100], ",", 42 clamp [0,100];')"

check "binds tighter than arithmetic" "30" \
  "$("$PHP" -r 'echo 2 max 3 * 10;')"

check "binds tighter than comparison" "1" \
  "$("$PHP" -r 'echo 1 max 5 > 3 ? 1 : 0;')"

check "namespace-local wins, global falls back" "7,10" \
  "$("$PHP" -r 'namespace App; function dist($a,$b){ return \abs($a-$b); }
                echo 3 dist 10, ",", 3 max 10;')"

check "undefined infix name errors normally" "err" \
  "$("$PHP" -r 'try { $x = 1 nosuch 2; } catch (\Error $e) { echo "err"; }')"

check "normal call syntax unchanged" "7 OK" \
  "$("$PHP" -r 'echo max(3,7), " ", strtoupper("ok");')"

echo "----"
echo "passed: $pass, failed: $fail"
[ "$fail" -eq 0 ]
