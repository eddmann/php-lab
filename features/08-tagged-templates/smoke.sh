#!/usr/bin/env bash
# Smoke tests for tagged template strings. Run after scripts/build.sh.
set -uo pipefail

REPO_ROOT="$(cd "$(dirname "${BASH_SOURCE[0]}")/../.." && pwd)"
PHP="$REPO_ROOT/php-src/sapi/cli/php"

pass=0; fail=0
check() { # name | expected | actual
  if [ "$2" = "$3" ]; then echo "ok   - $1"; pass=$((pass+1));
  else echo "FAIL - $1 (expected [$2], got [$3])"; fail=$((fail+1)); fi
}

TAG='function tag(array $s, array $v): string { return json_encode($s)."::".json_encode($v); }'

check "splits strings and values" '["a "," b"]::[42]' \
  "$("$PHP" -r "$TAG"' $x=42; echo tag"a {$x} b";')"

check "no interpolation" '["plain"]::[]' \
  "$("$PHP" -r "$TAG"' echo tag"plain";')"

check "adjacent interpolations padded" '["","",""]::[1,2]' \
  "$("$PHP" -r "$TAG"' $a=1;$b=2; echo tag"{$a}{$b}";')"

check "sql demo escapes strings" "id = 5 AND n = 'O''B'" \
  "$("$PHP" -r '$id=5;$n="O'"'"'B"; echo sql"id = {$id} AND n = {$n}";')"

check "sql null/bool" "NULL,TRUE" \
  "$("$PHP" -r '$a=null;$b=true; echo sql"{$a},{$b}";')"

check "ordinary string unaffected" "v=7" \
  "$("$PHP" -r '$x=7; echo "v={$x}";')"

echo "----"
echo "passed: $pass, failed: $fail"
[ "$fail" -eq 0 ]
