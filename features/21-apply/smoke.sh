#!/usr/bin/env bash
# Smoke tests for `apply` builder blocks (Kotlin-style lambda-with-receiver).
# Run after scripts/build.sh.
set -uo pipefail

REPO_ROOT="$(cd "$(dirname "${BASH_SOURCE[0]}")/../.." && pwd)"
PHP="$REPO_ROOT/php-src/sapi/cli/php"

pass=0; fail=0
check() { # name | expected | actual
  if [ "$2" = "$3" ]; then echo "ok   - $1"; pass=$((pass+1));
  else echo "FAIL - $1 (expected [$2], got [$3])"; fail=$((fail+1)); fi
}

check "binds \$this and returns the receiver" "3,4" \
  "$("$PHP" -r 'class B{public int $x=0; public int $y=0;} $b = apply(new B()){ $this->x=3; $this->y=4; }; echo "$b->x,$b->y";')"

check "auto-captures surrounding variables by value" "db:5432" \
  "$("$PHP" -r 'class C{public string $s="";} $h="db"; $p=5432; $c=apply(new C()){ $this->s="$h:$p"; }; echo $c->s;')"

check "capture is by value at the apply point" "1" \
  "$("$PHP" -r 'class C{public int $v=0;} $n=1; $c=apply(new C()){ $this->v=$n; }; $n=999; echo $c->v;')"

check "nested apply rebinds \$this" "root:a,b" \
  "$("$PHP" -r 'class N{public string $n=""; public array $k=[];}
                $t=apply(new N()){ $this->n="root"; $this->k[]=apply(new N()){ $this->n="a"; }; $this->k[]=apply(new N()){ $this->n="b"; }; };
                echo $t->n, ":", implode(",", array_map(fn($x)=>$x->n, $t->k));')"

check "control flow inside the block" "A,B,C" \
  "$("$PHP" -r 'class B{public array $i=[];} $seed=["a","b","c"]; $b=apply(new B()){ foreach($seed as $s) $this->i[]=strtoupper($s); }; echo implode(",", $b->i);')"

check "inside a method, block \$this is the receiver" "F-x" \
  "$("$PHP" -r 'class B{public array $i=[];}
                class F{ public string $p="F-"; function make($s){ $p=$this->p; return apply(new B()){ $this->i[]=$p.$s; }; } }
                echo (new F)->make("x")->i[0];')"

check "parenthesised result can be dereferenced" "42" \
  "$("$PHP" -r 'class C{public int $n=0; function get(){return $this->n;}} echo (apply(new C()){ $this->n=42; })->get();')"

check "exception in the block propagates" "caught: boom" \
  "$("$PHP" -r 'class C{} try { apply(new C()){ throw new RuntimeException("boom"); }; } catch (Throwable $e){ echo "caught: ", $e->getMessage(); }')"

check "receiver expression evaluated once" "make 1" \
  "$("$PHP" -r 'class B{public array $i=[];} function mk(){ echo "make "; return new B(); } $b=apply(mk()){ $this->i[]=1; }; echo count($b->i);')"

check "apply still usable as a method name" "svc:go" \
  "$("$PHP" -r 'class S{ function apply($s){ return "svc:$s"; } } echo (new S)->apply("go");')"

check "T_APPLY exposed to the tokenizer" "bool(true)" \
  "$("$PHP" -r 'var_dump(defined("T_APPLY"));')"

echo "----"
echo "passed: $pass, failed: $fail"
[ "$fail" -eq 0 ]
