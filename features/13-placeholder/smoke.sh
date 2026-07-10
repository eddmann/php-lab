#!/usr/bin/env bash
# Smoke tests for placeholder lambdas (Scala `_` / Raku Whatever `*`).
# Run after scripts/build.sh.
set -uo pipefail

REPO_ROOT="$(cd "$(dirname "${BASH_SOURCE[0]}")/../.." && pwd)"
PHP="$REPO_ROOT/php-src/sapi/cli/php"

pass=0; fail=0
check() { # name | expected | actual
  if [ "$2" = "$3" ]; then echo "ok   - $1"; pass=$((pass+1));
  else echo "FAIL - $1 (expected [$2], got [$3])"; fail=$((fail+1)); fi
}

check "one placeholder -> unary closure" "2,4,6" \
  "$("$PHP" -r 'echo implode(",", array_map(_ * 2, [1,2,3]));')"

check "two placeholders -> binary comparator" "1,2,5,8" \
  "$("$PHP" -r '$x=[5,2,8,1]; usort($x, _ <=> _); echo implode(",", $x);')"

check "predicate in array_filter" "1,2" \
  "$("$PHP" -r 'echo implode(",", array_filter([-1,0,1,2], _ > 0));')"

check "repeated placeholder reduces" "10" \
  "$("$PHP" -r 'echo array_reduce([1,2,3,4], _ + _, 0);')"

check "nested pipeline binds innermost" "11,21,31" \
  "$("$PHP" -r 'echo implode(",", array_map(_ + 1, array_map(_ * 10, [1,2,3])));')"

check "closure captures outer var by value" "101,102" \
  "$("$PHP" -r '$k=100; echo implode(",", array_map(_ + $k, [1,2]));')"

check "placeholder as method receiver" "10,12" \
  "$("$PHP" -r 'class B{function __construct(public int $v){} function d(){return $this->v*2;}}
                echo implode(",", array_map(_->d(), [new B(5), new B(6)]));')"

check "bare _ is the identity closure" "7,8" \
  "$("$PHP" -r 'echo implode(",", array_map(_, [7,8]));')"

check "user function named _ is still a call" "[hi]" \
  "$("$PHP" -r 'function _($s){ return "[$s]"; } echo _("hi");')"

check "bare _ constant outside call args is untouched" "7" \
  "$("$PHP" -r 'const _ = 7; echo _;')"

check "placeholder inside a named argument" "12" \
  "$("$PHP" -r 'function apply(callable $f, int $n){ return $f($n); } echo apply(f: _ * 3, n: 4);')"

echo "----"
echo "passed: $pass, failed: $fail"
[ "$fail" -eq 0 ]
