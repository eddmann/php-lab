#!/usr/bin/env bash
# Smoke tests for UFCS (Uniform Function Call Syntax, borrowed from D / Nim).
# Run after scripts/build.sh.
set -uo pipefail

REPO_ROOT="$(cd "$(dirname "${BASH_SOURCE[0]}")/../.." && pwd)"
PHP="$REPO_ROOT/php-src/sapi/cli/php"

pass=0; fail=0
check() { # name | expected | actual
  if [ "$2" = "$3" ]; then echo "ok   - $1"; pass=$((pass+1));
  else echo "FAIL - $1 (expected [$2], got [$3])"; fail=$((fail+1)); fi
}

check "free function in method position, receiver first" "5" \
  "$("$PHP" -r 'class V{function __construct(public int $x, public int $y){}}
                function len(V $v){ return (int)sqrt($v->x**2 + $v->y**2); }
                echo (new V(3,4))->len();')"

check "extra arguments follow the receiver" "6,8" \
  "$("$PHP" -r 'class V{function __construct(public int $x, public int $y){}}
                function scale(V $v, int $k){ return new V($v->x*$k, $v->y*$k); }
                $s = (new V(3,4))->scale(2); echo "{$s->x},{$s->y}";')"

check "chaining across free functions" "9" \
  "$("$PHP" -r 'final class B{function __construct(public int $v){}}
                function inc(B $b){ return new B($b->v+1); }
                function dbl(B $b){ return new B($b->v*2); }
                echo (new B(3))->inc()->dbl()->inc()->v;')"

check "real method wins over free function" "method" \
  "$("$PHP" -r 'function label($o){ return "free"; }
                class C{ function label(){ return "method"; } }
                echo (new C)->label();')"

check "__call wins over UFCS" "__call:ping" \
  "$("$PHP" -r 'function ping($o){ return "free"; }
                class C{ function __call($n,$a){ return "__call:$n"; } }
                echo (new C)->ping();')"

check "reentrancy: free fn body uses UFCS" "11" \
  "$("$PHP" -r 'class B{function __construct(public int $v){}}
                function outer(B $b){ return $b->twice()+1; }
                function twice(B $b){ return $b->v*2; }
                echo (new B(5))->outer();')"

check "spread args through UFCS" "16" \
  "$("$PHP" -r 'class B{function __construct(public int $v){}}
                function total(B $b, int ...$xs){ return $b->v + array_sum($xs); }
                $a=[1,2,3]; echo (new B(10))->total(...$a);')"

check "exception from free fn propagates" "caught: bang" \
  "$("$PHP" -r 'class C{}
                function boom(C $c){ throw new RuntimeException("bang"); }
                try { (new C)->boom(); } catch (Throwable $e){ echo "caught: ", $e->getMessage(); }')"

check "no method and no free function still errors" "err" \
  "$("$PHP" -r 'class C{} try { (new C)->nope(); } catch (\Error $e){ echo "err"; }')"

check "global function resolves from a namespaced call site" "14" \
  "$("$PHP" -r 'namespace { function dbl($o){ return $o->v*2; } }
                namespace App { class C{ function __construct(public int $v){} }
                  echo (new C(7))->dbl(); }')"

echo "----"
echo "passed: $pass, failed: $fail"
[ "$fail" -eq 0 ]
