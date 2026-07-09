#!/usr/bin/env bash
# Smoke tests for `with` expressions (clone-with sugar). Run after scripts/build.sh.
set -uo pipefail

REPO_ROOT="$(cd "$(dirname "${BASH_SOURCE[0]}")/../.." && pwd)"
PHP="$REPO_ROOT/php-src/sapi/cli/php"

pass=0; fail=0
check() { # name | expected | actual
  if [ "$2" = "$3" ]; then echo "ok   - $1"; pass=$((pass+1));
  else echo "FAIL - $1 (expected [$2], got [$3])"; fail=$((fail+1)); fi
}

CLS='class P { public function __construct(public int $a=0, public int $b=0) {} }'

check "basic override" "10,0" \
  "$("$PHP" -r "$CLS"' $p=new P(1,0); $q=$p with { a: 10 }; echo "$q->a,$q->b";')"

check "original unchanged" "1,2" \
  "$("$PHP" -r "$CLS"' $p=new P(1,2); $q=$p with { a: 9 }; echo "$p->a,$p->b";')"

check "value references original" "42" \
  "$("$PHP" -r "$CLS"' $p=new P(1); echo ($p with { a: $p->a + 41 })->a;')"

check "spread, later wins" "7,80" \
  "$("$PHP" -r "$CLS"' $p=new P(); $o=["a"=>7,"b"=>8]; $q=$p with { ...$o, b: 80 }; echo "$q->a,$q->b";')"

check "trailing comma" "5" \
  "$("$PHP" -r "$CLS"' $p=new P(); echo ($p with { a: 5, })->a;')"

check "chaining" "11,22" \
  "$("$PHP" -r "$CLS"' $p=new P(); $q=$p with { a: 11 } with { b: 22 }; echo "$q->a,$q->b";')"

check "on a new expression" "2" \
  "$("$PHP" -r "$CLS"' echo ((new P(1)) with { a: 2 })->a;')"

check "with usable as method name" "method:x" \
  "$("$PHP" -r 'class B { function with($k){ return "method:$k"; } } echo (new B)->with("x");')"

check "with usable as static method" "static:y" \
  "$("$PHP" -r 'class M { static function with($k){ return "static:$k"; } } echo M::with("y");')"

echo "----"
echo "passed: $pass, failed: $fail"
[ "$fail" -eq 0 ]
