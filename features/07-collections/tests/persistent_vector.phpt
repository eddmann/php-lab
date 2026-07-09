--TEST--
Vector — persistent immutable ordered sequence (structural sharing)
--FILE--
<?php
// Construction.
$v = Vector::of(1, 2, 3);
echo $v, "\n";
echo count($v), " ", $v->isEmpty() ? "empty" : "nonempty", "\n";
echo new Vector([10, 20, 30]), "\n";
echo Vector::fromArray([7, 8]), "\n";

// Access.
echo $v->get(0), $v->get(2), " ", $v->first(), $v->last(), "\n";

// Immutability: every "mutation" returns a new value; the original is untouched.
$a = Vector::of(1, 2, 3);
$b = $a->push(4);
$c = $a->set(1, 99);
$d = $a->pop();
echo $a, " ", $b, " ", $c, " ", $d, "\n";

// Transforms.
$nums = Vector::of(1, 2, 3, 4);
echo $nums->map(fn($x) => $x * 10), "\n";
echo $nums->filter(fn($x) => $x % 2 === 0), "\n";
echo $nums->reduce(fn($acc, $x) => $acc + $x, 0), "\n";
echo $nums->slice(1, 2), "\n";

// Concatenation: method and `+` operator.
echo Vector::of(1, 2)->concat(Vector::of(3, 4)), "\n";
echo Vector::of(1, 2) + Vector::of(3, 4), "\n";

// Iteration.
$pairs = [];
foreach (Vector::of('a', 'b', 'c') as $i => $x) {
    $pairs[] = "$i:$x";
}
echo implode(' ', $pairs), "\n";

// Large-N integrity across trie height-growth boundaries (32, 1024, …).
$big = Vector::of();
for ($i = 0; $i < 3000; $i++) {
    $big = $big->push($i);
}
$ok = count($big) === 3000 && $big->get(0) === 0 && $big->get(32) === 32
   && $big->get(1024) === 1024 && $big->last() === 2999;
echo $ok ? "big ok" : "big BAD", "\n";

// Structural sharing leaves the source intact after a deep update.
$src = Vector::fromArray(range(0, 2000));
$upd = $src->set(1500, -1);
echo $src->get(1500), " ", $upd->get(1500), "\n";

// Bounds checks throw.
try { Vector::of(1)->get(5); } catch (\Error $e) { echo "range error\n"; }
try { Vector::of()->pop(); } catch (\Error $e) { echo "empty pop error\n"; }
?>
--EXPECT--
Vector(1, 2, 3)
3 nonempty
Vector(10, 20, 30)
Vector(7, 8)
13 13
Vector(1, 2, 3) Vector(1, 2, 3, 4) Vector(1, 99, 3) Vector(1, 2)
Vector(10, 20, 30, 40)
Vector(2, 4)
10
Vector(2, 3)
Vector(1, 2, 3, 4)
Vector(1, 2, 3, 4)
0:a 1:b 2:c
big ok
1500 -1
range error
empty pop error
