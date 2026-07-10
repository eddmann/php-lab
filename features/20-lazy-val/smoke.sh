#!/usr/bin/env bash
# Smoke tests for `lazy` locals (Scala-style lazy val).
# Run after scripts/build.sh.
set -uo pipefail

REPO_ROOT="$(cd "$(dirname "${BASH_SOURCE[0]}")/../.." && pwd)"
PHP="$REPO_ROOT/php-src/sapi/cli/php"

pass=0; fail=0
check() { # name | expected | actual
  if [ "$2" = "$3" ]; then echo "ok   - $1"; pass=$((pass+1));
  else echo "FAIL - $1 (expected [$2], got [$3])"; fail=$((fail+1)); fi
}

check "deferred: initializer runs only on first read" "before computing 42" \
  "$("$PHP" -r 'function m(){ echo "computing "; return 42; } lazy $x = m(); echo "before "; echo $x;')"

check "memoised: second read does not recompute" "run 7 7" \
  "$("$PHP" -r 'lazy $x = (function(){ echo "run "; return 7; })(); echo $x, " ", $x;')"

check "never read -> never computed" "done" \
  "$("$PHP" -r 'lazy $y = (function(){ echo "NOPE"; return 1; })(); echo "done";')"

check "captures scope by value at declaration" "11" \
  "$("$PHP" -r '$a=10; lazy $s = $a + 1; $a=999; echo $s;')"

check "a lazy may force an earlier lazy" "base 10" \
  "$("$PHP" -r 'lazy $a = (function(){ echo "base "; return 5; })(); lazy $b = $a * 2; echo $b;')"

check "isset does not force" "bool(true)" \
  "$("$PHP" -r 'lazy $v = (function(){ echo "NOPE"; return 1; })(); var_dump(isset($v));')"

check "exception in initializer propagates on read" "caught: boom" \
  "$("$PHP" -r 'lazy $x = (function(){ throw new RuntimeException("boom"); })();
                try { echo $x; } catch (Throwable $e){ echo "caught: ", $e->getMessage(); }')"

check "a lazy object is mutable by handle" "once" \
  "$("$PHP" -r 'lazy $o = new stdClass(); $o->tag = "once"; echo $o->tag;')"

check "reads compose with array functions" "15" \
  "$("$PHP" -r 'lazy $xs = range(1,5); echo array_sum($xs);')"

# --- read-only binding: writes are compile errors (checked via subprocess) ---
err() { "$PHP" -r "$1" 2>&1; }
check "direct rebind is a compile error" "yes" \
  "$(case "$(err 'lazy $x=1; $x=2;')" in *"Cannot modify lazy variable"*) echo yes;; esac)"
check "compound assign is a compile error" "yes" \
  "$(case "$(err 'lazy $x=1; $x+=2;')" in *"Cannot modify lazy variable"*) echo yes;; esac)"
# Aliasing forces the value into a detached copy; the lazy binding stays read-only.
check "by-reference alias cannot mutate the lazy" "10" \
  "$("$PHP" -r 'lazy $x=10; $y=&$x; $y=999; echo $x;')"

check "lazy still usable as a method name" "ok" \
  "$("$PHP" -r 'class C { function lazy(){ return "ok"; } } echo (new C)->lazy();')"

check "T_LAZY exposed to the tokenizer" "bool(true)" \
  "$("$PHP" -r 'var_dump(defined("T_LAZY"));')"

echo "----"
echo "passed: $pass, failed: $fail"
[ "$fail" -eq 0 ]
