#!/usr/bin/env bash
# Smoke tests for `for {} yield` comprehensions (arrays + Option/Result).
# Run after scripts/build.sh.
set -uo pipefail

REPO_ROOT="$(cd "$(dirname "${BASH_SOURCE[0]}")/../.." && pwd)"
PHP="$REPO_ROOT/php-src/sapi/cli/php"

pass=0; fail=0
check() { # name | expected | actual
  if [ "$2" = "$3" ]; then echo "ok   - $1"; pass=$((pass+1));
  else echo "FAIL - $1 (expected [$2], got [$3])"; fail=$((fail+1)); fi
}

check "array cartesian" "11,21,12,22,13,23" \
  "$("$PHP" -r 'echo implode(",", for { $x = [1,2,3]; $y = [10,20] } yield $x+$y);')"

check "array single = map" "2,4,6" \
  "$("$PHP" -r 'echo implode(",", for { $x = [1,2,3] } yield $x*2);')"

check "array guard" "4,16" \
  "$("$PHP" -r 'echo implode(",", for { $x = [1,2,3,4]; if $x%2==0 } yield $x*$x);')"

check "array dependent generator" "1:1,2:1,2:2,3:1,3:2,3:3" \
  "$("$PHP" -r 'echo implode(",", for { $x = [1,2,3]; $y = range(1,$x) } yield "$x:$y");')"

check "Option all Some" "Some(3)" \
  "$("$PHP" -r 'echo for { $x = Some(1); $y = Some(2) } yield $x+$y;')"

check "Option None short-circuits" "None" \
  "$("$PHP" -r 'echo for { $x = Some(1); $y = None() } yield $x+$y;')"

check "Result Ok chain" "Ok(20)" \
  "$("$PHP" -r 'echo for { $x = Ok(4); $y = Ok(5) } yield $x*$y;')"

check "Result Err short-circuits" 'Err("e")' \
  "$("$PHP" -r 'echo for { $x = Ok(1); $y = Err("e") } yield $x+$y;')"

check "Option guard via filter" "None" \
  "$("$PHP" -r 'echo for { $x = Some(4); if $x > 10 } yield $x;')"

check "nested comprehension" "3,4;6,8" \
  "$("$PHP" -r 'echo implode(";", for { $x=[1,2] } yield implode(",", for { $y=[3,4] } yield $x*$y));')"

check "mixing array+Option errors" "caught" \
  "$("$PHP" -r 'try { for { $x=[1,2]; $y=Some(3) } yield $x+$y; } catch(\Error $e){ echo "caught"; }')"

# Dict comprehension (yield k => v) builds an associative array.
check "dict comprehension" "10:a,20:b" \
  "$("$PHP" -r '$u=[["id"=>10,"n"=>"a"],["id"=>20,"n"=>"b"]]; $m=for { $x=$u } yield $x["id"]=>$x["n"]; $o=[]; foreach($m as $k=>$v)$o[]="$k:$v"; echo implode(",",$o);')"

check "dict comprehension later key wins" "k:3" \
  "$("$PHP" -r '$m=for { $x=[1,2,3] } yield "k"=>$x; echo "k:".$m["k"];')"

# Set comprehensions (iterating a `Set` source) rely on feature 07's collections,
# which this self-contained feature does not bundle — that case only works when
# both features are applied, so it isn't smoke-tested in this standalone build.

# Ordinary for-loops are unaffected.
check "regular for loop intact" "0123" \
  "$("$PHP" -r 'for ($i=0;$i<4;$i++) echo $i;')"

echo "----"
echo "passed: $pass, failed: $fail"
[ "$fail" -eq 0 ]
