--TEST--
UFCS: a free function called in method position, receiver passed first
--FILE--
<?php
final class Vec {
    public function __construct(public int $x, public int $y) {}
}

// `->len()` -> len($v); `->add($o)` -> add($v, $o).
function len(Vec $v): float { return sqrt($v->x ** 2 + $v->y ** 2); }
function add(Vec $a, Vec $b): Vec { return new Vec($a->x + $b->x, $a->y + $b->y); }
function scale(Vec $v, int $k): Vec { return new Vec($v->x * $k, $v->y * $k); }

$v = new Vec(3, 4);
echo $v->len(), "\n";                       // 5

// Extra arguments follow the receiver.
$s = $v->scale(2);
echo "{$s->x},{$s->y}\n";                    // 6,8

// Chaining: each free function returns a Vec.
$r = $v->add(new Vec(1, 1))->scale(10);
echo "{$r->x},{$r->y}\n";                    // 40,50

// A real method always wins over a same-named free function.
function label(Vec $v): string { return "free"; }
class Named {
    public function __construct(public int $x, public int $y) {}
    public function label(): string { return "method"; }
}
echo (new Named(0, 0))->label(), "\n";       // method

// __call still takes precedence over UFCS.
function ping($o): string { return "free"; }
class WithCall {
    public function __call($name, $args): string { return "__call:$name"; }
}
echo (new WithCall)->ping(), "\n";           // __call:ping

// No method and no matching free function -> the usual error.
class Bare {}
try {
    (new Bare)->missing();
} catch (\Error $e) {
    echo $e->getMessage(), "\n";
}
?>
--EXPECT--
5
6,8
40,50
method
__call:ping
Call to undefined method Bare::missing()
