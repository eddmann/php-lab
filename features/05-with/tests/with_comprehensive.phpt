--TEST--
with — fields, spread, chaining, __clone, expression operand
--FILE--
<?php
class P { public function __construct(public int $age, public string $status = "new") {} }
$p = new P(30);
$q = $p with { age: 31 };
printf("orig=%d,%s  new=%d,%s\n", $p->age, $p->status, $q->age, $q->status);
// multiple fields + expression values
$r = $p with { age: $p->age + 10, status: 'active' };
printf("r=%d,%s\n", $r->age, $r->status);
// spread overrides, later wins
$over = ['age' => 99, 'status' => 'spread'];
$s = $p with { ...$over, status: 'explicit' };
printf("s=%d,%s\n", $s->age, $s->status);
// chaining
$t = $p with { age: 1 } with { age: 2 } with { status: 'chained' };
printf("t=%d,%s\n", $t->age, $t->status);
// __clone is honoured
class Q { public array $tags = []; public function __clone() { print "cloned\n"; } public function __construct(public int $n) {} }
$a = new Q(1);
$b = $a with { n: 2 };
printf("b->n=%d\n", $b->n);
// expression operand
$u = (new P(5)) with { age: 6 };
printf("u=%d\n", $u->age);
--EXPECT--
orig=30,new  new=31,new
r=40,active
s=99,explicit
t=2,chained
cloned
b->n=2
u=6
