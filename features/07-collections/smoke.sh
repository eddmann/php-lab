#!/usr/bin/env bash
# Smoke tests for the persistent collections (Vector / Map / Set).
# Run after scripts/build.sh.
set -uo pipefail

REPO_ROOT="$(cd "$(dirname "${BASH_SOURCE[0]}")/../.." && pwd)"
PHP="$REPO_ROOT/php-src/sapi/cli/php"

pass=0; fail=0
check() { # name | expected | actual
  if [ "$2" = "$3" ]; then echo "ok   - $1"; pass=$((pass+1));
  else echo "FAIL - $1 (expected [$2], got [$3])"; fail=$((fail+1)); fi
}

# --- Vector ---
check "vector of/toString" "Vector(1, 2, 3)" \
  "$("$PHP" -r 'echo Vector::of(1,2,3);')"
check "vector push immutable" "Vector(1, 2, 3)|Vector(1, 2, 3, 4)" \
  "$("$PHP" -r '$a=Vector::of(1,2,3); $b=$a->push(4); echo "$a|$b";')"
check "vector set immutable" "Vector(1, 2, 3)|Vector(1, 9, 3)" \
  "$("$PHP" -r '$a=Vector::of(1,2,3); $b=$a->set(1,9); echo "$a|$b";')"
check "vector map/filter/reduce" "Vector(2, 4, 6)|Vector(2)|6" \
  "$("$PHP" -r '$v=Vector::of(1,2,3); echo $v->map(fn($x)=>$x*2),"|",$v->filter(fn($x)=>$x==2),"|",$v->reduce(fn($a,$b)=>$a+$b,0);')"
check "vector + operator" "Vector(1, 2, 3, 4)" \
  "$("$PHP" -r 'echo Vector::of(1,2) + Vector::of(3,4);')"
check "vector large-N integrity" "ok" \
  "$("$PHP" -r '$v=Vector::of(); for($i=0;$i<2000;$i++)$v=$v->push($i); echo ($v->get(1024)===1024 && count($v)===2000)?"ok":"bad";')"

# --- Map ---
check "map get" "1" \
  "$("$PHP" -r 'echo Map::fromArray(["a"=>1])->get("a");')"
check "map set immutable" "1|2" \
  "$("$PHP" -r '$a=Map::fromArray(["x"=>1]); $b=$a->set("y",2); echo count($a),"|",count($b);')"
check "map int vs string key" "int|str" \
  "$("$PHP" -r '$m=(new Map())->set(1,"int")->set("1","str"); echo $m->get(1),"|",$m->get("1");')"
check "map get default" "DEF" \
  "$("$PHP" -r 'echo Map::fromArray([])->get("x","DEF");')"

# --- Set ---
check "set dedup count" "3" \
  "$("$PHP" -r 'echo count(Set::of(1,2,2,3,3));')"
check "set operators" "Set(1)" \
  "$("$PHP" -r 'echo (Set::of(1,2,3) - Set::of(2,3,4));')"
check "set union/intersect counts" "4|2" \
  "$("$PHP" -r '$a=Set::of(1,2,3);$b=Set::of(2,3,4); echo count($a|$b),"|",count($a&$b);')"

echo "----"
echo "passed: $pass, failed: $fail"
[ "$fail" -eq 0 ]
