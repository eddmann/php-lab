#!/usr/bin/env bash
# Smoke tests for `val` write-once locals. Run after scripts/build.sh.
# Each blocked form is checked in its own process (a compile error aborts the file).
set -uo pipefail

REPO_ROOT="$(cd "$(dirname "${BASH_SOURCE[0]}")/../.." && pwd)"
PHP="$REPO_ROOT/php-src/sapi/cli/php"

pass=0; fail=0
check() { # name | expected | actual
  if [ "$2" = "$3" ]; then echo "ok   - $1"; pass=$((pass+1));
  else echo "FAIL - $1 (expected [$2], got [$3])"; fail=$((fail+1)); fi
}

# blocked: a snippet that should fail to compile with "Cannot reassign val"
blocked() { # name | php-code
  out="$("$PHP" -r "$2" 2>&1 || true)"
  case "$out" in
    *"Cannot reassign val"*) echo "ok   - $1 (blocked)"; pass=$((pass+1));;
    *) echo "FAIL - $1 (not blocked: [$out])"; fail=$((fail+1));;
  esac
}

# --- reads work ---
check "read" "5" "$("$PHP" -r 'val $x = 5; echo $x;')"
check "expression value" "63" "$("$PHP" -r 'function dbl($n){return $n*2;} val $x=21; echo dbl($x)+$x;')"
check "foreach source (read)" "6" "$("$PHP" -r 'val $xs=[1,2,3]; $s=0; foreach($xs as $v)$s+=$v; echo $s;')"
check "plain var still reassignable" "2" "$("$PHP" -r '$x=1; $x=2; echo $x;')"

# --- every write form is a compile error ---
blocked "reassign"            'val $x = 1; $x = 2;'
blocked "compound assign"     'val $x = 1; $x += 1;'
blocked "post-increment"      'val $x = 1; $x++;'
blocked "pre-decrement"       'val $x = 1; --$x;'
blocked "assign-ref target"   'val $x = 1; $y =& $x;'
blocked "assign-ref source"   'val $x = 1; $x =& $y;'
blocked "unset"               'val $x = 1; unset($x);'
blocked "foreach value"       'val $x = 1; foreach ([1] as $x) {}'
blocked "foreach key"         'val $x = 1; foreach ([1] as $x => $v) {}'
blocked "re-declare val"      'val $x = 1; val $x = 2;'

# --- semi-reserved: usable as member names ---
check "val as method name" "m7" \
  "$("$PHP" -r 'class C { function val($n){ return "m$n"; } } echo (new C)->val(7);')"
check "val as const name" "42" \
  "$("$PHP" -r 'class C { const val = 42; } echo C::val;')"

# --- scope isolation ---
check "same name independent across functions" "10,20" \
  "$("$PHP" -r 'function a(){ val $v=10; return $v; } function b(){ val $v=20; return $v; } echo a().",".b();')"

echo "----"
echo "passed: $pass, failed: $fail"
[ "$fail" -eq 0 ]
