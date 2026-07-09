#!/usr/bin/env bash
# Smoke tests for the `unless` construct. Run after scripts/build.sh.
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

check "block: false cond runs body" "A" \
  "$("$PHP" -r 'unless (false) { echo "A"; }')"

check "block: true cond skips body" "B" \
  "$("$PHP" -r 'unless (true) { echo "X"; } echo "B";')"

check "else: true cond runs else" "C" \
  "$("$PHP" -r 'unless (true) { echo "X"; } else { echo "C"; }')"

check "else: false cond runs body" "D" \
  "$("$PHP" -r 'unless (false) { echo "D"; } else { echo "X"; }')"

check "modifier: false cond runs stmt" "E" \
  "$("$PHP" -r 'print "E" unless false;')"

check "modifier: true cond skips stmt" "F" \
  "$("$PHP" -r 'print "X" unless true; echo "F";')"

# Equivalence with `if (!...)`: body runs only when ($x > 0) is false, i.e. x=-1.
check "equivalence with if(!cond)" "nonpos:-1 " \
  "$("$PHP" -r 'foreach ([1,-1] as $x) { unless ($x > 0) { echo "nonpos:$x "; } }')"

# unless stays usable as a method name (semi-reserved, no BC break)
check "usable as method name" "ok" \
  "$("$PHP" -r 'class C { function unless() { return "ok"; } } echo (new C)->unless();')"

# Tokenizer exposes T_UNLESS to userland
check "tokenizer exposes T_UNLESS" "bool(true)" \
  "$("$PHP" -r 'var_dump(in_array("T_UNLESS", array_map("token_name", array_column(token_get_all("<?php unless(true){}"), 0))));')"

# Regression: ordinary if/else still works
check "regression: if/else intact" "yes" \
  "$("$PHP" -r 'if (1 > 0) { echo "yes"; } else { echo "no"; }')"

echo "----"
echo "passed: $pass, failed: $fail"
[ "$fail" -eq 0 ]
