#!/usr/bin/env bash
# Smoke tests for the `defer` statement (Go-style scope-exit cleanup).
# Run after scripts/build.sh.
set -uo pipefail

REPO_ROOT="$(cd "$(dirname "${BASH_SOURCE[0]}")/../.." && pwd)"
PHP="$REPO_ROOT/php-src/sapi/cli/php"

pass=0; fail=0
check() { # name | expected | actual
  if [ "$2" = "$3" ]; then echo "ok   - $1"; pass=$((pass+1));
  else echo "FAIL - $1 (expected [$2], got [$3])"; fail=$((fail+1)); fi
}

check "LIFO order" "body ba" \
  "$("$PHP" -r 'function f(){ defer printf("a"); defer printf("b"); echo "body "; } f();')"

check "runs on exception" "cleanup caught" \
  "$("$PHP" -r 'function f(){ defer printf("cleanup "); throw new Exception("x"); }
                try { f(); } catch (Throwable $e) { echo "caught"; }')"

check "runs in a loop (LIFO)" "210" \
  "$("$PHP" -r 'function f(){ for($i=0;$i<3;$i++) defer printf("%d",$i); } f();')"

check "Go-faithful arg capture" "1" \
  "$("$PHP" -r 'function f(){ $x=1; defer printf("%d",$x); $x=2; } f();')"

check "receiver captured at defer point" "first" \
  "$("$PHP" -r 'class L{function __construct(public $t){} function f(){ echo $this->t; }}
                function g(){ $l=new L("first"); defer $l->f(); $l=new L("second"); } g();')"

check "static call" "bye" \
  "$("$PHP" -r 'class G{static function b(){ echo "bye"; }} function f(){ defer G::b(); } f();')"

check "spread args captured" "1-2-3" \
  "$("$PHP" -r 'function f(){ $a=[1,2,3]; defer printf("%d-%d-%d",...$a); $a=[9]; } f();')"

check "return value unaffected" "int(42)" \
  "$("$PHP" -r 'function f(){ defer printf(""); return 42; } var_dump(f());')"

noncall_out="$("$PHP" -r 'function f(){ $x=1; defer $x+1; }' 2>&1 || true)"
check "non-call operand is a compile error" "compile-error" \
  "$(case "$noncall_out" in *"defer requires"*) echo "compile-error";; esac)"

check "defer still usable as a method name" "ok" \
  "$("$PHP" -r 'class C{ function defer(){ return "ok"; } } echo (new C)->defer();')"

echo "----"
echo "passed: $pass, failed: $fail"
[ "$fail" -eq 0 ]
