#!/usr/bin/env bash
# Smoke tests for lazy (by-name) parameters (ALGOL 60 / Scala).
# Run after scripts/build.sh.
set -uo pipefail

REPO_ROOT="$(cd "$(dirname "${BASH_SOURCE[0]}")/../.." && pwd)"
PHP="$REPO_ROOT/php-src/sapi/cli/php"

pass=0; fail=0
check() { # name | expected | actual
  if [ "$2" = "$3" ]; then echo "ok   - $1"; pass=$((pass+1));
  else echo "FAIL - $1 (expected [$2], got [$3])"; fail=$((fail+1)); fi
}

check "unused lazy argument never evaluates" "quiet" \
  "$("$PHP" -r 'function boom(){ echo "BOOM"; return "x"; }
                function f(bool $on, lazy $m){ if ($on) echo $m; }
                f(false, boom()); echo "quiet";')"

check "used lazy argument evaluates once (memoized)" "eval:126" \
  "$("$PHP" -r 'function once(){ echo "eval:"; return 42; }
                function f(lazy $x){ return $x + $x + $x; } echo f(once());')"

check "body runs before arguments evaluate" ":-body-BABA" \
  "$("$PHP" -r 'function t($g){ echo $g; return $g; }
                function g(lazy $a, lazy $b){ echo "-body-"; return $b . $a; }
                echo ":", g(t("A"), t("B"));')"

check "captured by value at the call site" "11" \
  "$("$PHP" -r 'function f(lazy $x){ return $x; } $n=1; $r=f($n+10); $n=99; echo $r;')"

check "throwing argument throws only when forced" "skipped/caught:late" \
  "$("$PHP" -r 'function risky(){ throw new RuntimeException("late"); }
                function g(bool $u, lazy $v){ if(!$u) return "skipped";
                    try { return "got:".$v; } catch (Throwable $e){ return "caught:".$e->getMessage(); } }
                echo g(false, risky()), "/", g(true, risky());')"

check "assert idiom: free diagnostics on the happy path" "ok caught:diag" \
  "$("$PHP" -r 'function assert_that(bool $c, lazy $m){ if(!$c) throw new AssertionError($m); return "ok"; }
                function expensive(){ return "diag"; }
                echo assert_that(true, expensive()), " ";
                try { assert_that(false, expensive()); } catch (AssertionError $e){ echo "caught:", $e->getMessage(); }')"

check "user-defined control flow terminates recursion" "3628800" \
  "$("$PHP" -r 'function ite(bool $c, lazy $t, lazy $e){ return $c ? $t : $e; }
                function fact(int $n): int { return ite($n <= 1, 1, $n * fact($n - 1)); }
                echo fact(10);')"

check "closure in body shares the memoized force" "eval#1:15" \
  "$("$PHP" -r 'function ce(){ static $n=0; $n++; echo "eval#$n:"; return 7; }
                function f(lazy $x){ $g = fn() => $x + 1; return $g() + $x; } echo f(ce());')"

check "dynamic call degrades to eager but stays correct" "DYNAMIC" \
  "$("$PHP" -r 'function shout(lazy $s){ return strtoupper($s); } $f="shout"; echo $f("dynamic");')"

wr="$("$PHP" -r 'function f(lazy $x){ $x = 5; }' 2>&1 || true)"
check "lazy parameters are read-only (compile error)" "readonly" \
  "$(case "$wr" in *"Cannot modify lazy parameter"*) echo readonly;; esac)"

ty="$("$PHP" -r 'function f(lazy int $x){}' 2>&1 || true)"
check "typed lazy parameter rejected" "rejected" \
  "$(case "$ty" in *"cannot declare a type"*) echo rejected;; esac)"

check "lazy still usable as a method name" "ok" \
  "$("$PHP" -r 'class C { function lazy(){ return "ok"; } } echo (new C)->lazy();')"

echo "----"
echo "passed: $pass, failed: $fail"
[ "$fail" -eq 0 ]
