--TEST--
with expressions — clone-with sugar (named fields, spread, chaining, readonly)
--FILE--
<?php
class P {
    public function __construct(public int $a, public int $b, public string $name) {}
}

// Basic override; original is untouched.
$p = new P(1, 2, "orig");
$q = $p with { a: 10 };
printf("p=%d,%d,%s q=%d,%d,%s\n", $p->a, $p->b, $p->name, $q->a, $q->b, $q->name);

// Multiple fields.
$r = $p with { a: 100, name: "changed" };
printf("r=%d,%d,%s\n", $r->a, $r->b, $r->name);

// Value expression may reference the original.
$s = $p with { a: $p->a + 41 };
echo $s->a, "\n";

// Spread a dynamic array of overrides; later members win.
$ov = ["a" => 7, "b" => 8];
$t = $p with { ...$ov, b: 80 };
printf("%d,%d\n", $t->a, $t->b);

// Trailing comma is allowed.
$u = $p with { a: 5, };
echo $u->a, "\n";

// Chaining.
$v = $p with { a: 11 } with { b: 22 };
printf("%d,%d\n", $v->a, $v->b);

// __clone() still runs.
class C { public int $n = 0; public function __clone() { echo "cloned\n"; } }
$c = new C();
$d = $c with { n: 9 };
echo $d->n, "\n";

// readonly: an in-class wither can set it; a global-scope attempt cannot.
final class Point {
    public function __construct(public readonly int $x, public readonly int $y) {}
    public function withY(int $y): static { return $this with { y: $y }; }
}
$pt = new Point(1, 2);
echo $pt->withY(9)->y, "\n";
try {
    $bad = $pt with { y: 3 };
} catch (\Error $e) {
    echo "readonly error\n";
}

// `with` remains usable as a method name (semi-reserved).
class Builder {
    public function with(string $k): string { return "method:$k"; }
}
echo (new Builder)->with("ok"), "\n";
?>
--EXPECT--
p=1,2,orig q=10,2,orig
r=100,2,changed
42
7,80
5
11,22
cloned
9
9
readonly error
method:ok
