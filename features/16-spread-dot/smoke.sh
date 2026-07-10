#!/usr/bin/env bash
# Smoke tests for spread-dot `*->` (Groovy-style spread operator).
# Run after scripts/build.sh.
set -uo pipefail

REPO_ROOT="$(cd "$(dirname "${BASH_SOURCE[0]}")/../.." && pwd)"
PHP="$REPO_ROOT/php-src/sapi/cli/php"

pass=0; fail=0
check() { # name | expected | actual
  if [ "$2" = "$3" ]; then echo "ok   - $1"; pass=$((pass+1));
  else echo "FAIL - $1 (expected [$2], got [$3])"; fail=$((fail+1)); fi
}

USER='class U { function __construct(public string $name, public int $age){}
             function greet(){ return "hi {$this->name}"; }
             function agePlus(int $b){ return $this->age + $b; } }
      $us = [new U("ada",36), new U("bob",40)];'

check "method spread over a collection" "hi ada,hi bob" \
  "$("$PHP" -r "$USER echo implode(',', \$us*->greet());")"

check "method spread with an argument" "46,50" \
  "$("$PHP" -r "$USER echo implode(',', \$us*->agePlus(10));")"

check "property spread" "ada,bob" \
  "$("$PHP" -r "$USER echo implode(',', \$us*->name);")"

check "keys are preserved" "x=cy,y=di" \
  "$("$PHP" -r 'class U{function __construct(public string $n){}}
                $m=["x"=>new U("cy"),"y"=>new U("di")];
                $out=[]; foreach ($m*->n as $k=>$v){ $out[]="$k=$v"; } echo implode(",",$out);')"

check "chaining spread-dot" "3,4,5" \
  "$("$PHP" -r 'class B{function __construct(public int $v){} function inc(){return new B($this->v+1);} function get(){return $this->v;}}
                $xs=[new B(1),new B(2),new B(3)];
                echo implode(",", $xs*->inc()*->inc()*->get());')"

check "empty collection yields empty array" "0" \
  "$("$PHP" -r 'class U{function greet(){return "x";}} echo count([]*->greet());')"

check "resolves global array_map from a namespace" "A,B" \
  "$("$PHP" -r 'namespace App; class U{function __construct(public string $n){} function up(){return strtoupper($this->n);}}
                $us=[new U("a"),new U("b")]; echo implode(",", $us*->up());')"

check "spread on a parenthesised expression" "10,20" \
  "$("$PHP" -r 'class B{function __construct(public int $v){} function get(){return $this->v;}}
                function mk(){ return [new B(10), new B(20)]; }
                echo implode(",", (mk())*->get());')"

check "multiplication / power / *= unaffected" "42|1024|12" \
  "$("$PHP" -r '$n=4; $n*=3; echo 6*7, "|", 2**10, "|", $n;')"

spread_bad="$("$PHP" -r '$x=5; try { $x*->foo(); } catch (\Throwable $e) { echo "typeerror"; }' 2>&1 || true)"
check "spread on a non-array raises a TypeError" "typeerror" \
  "$(case "$spread_bad" in *typeerror*) echo typeerror;; esac)"

echo "----"
echo "passed: $pass, failed: $fail"
[ "$fail" -eq 0 ]
