--TEST--
Set — persistent immutable set of unique members (HAMT, operators)
--FILE--
<?php
// Construction de-duplicates.
$s = Set::of(1, 2, 2, 3, 3, 3);
echo count($s), " ", $s->has(2) ? "y" : "n", $s->has(9) ? "y" : "n", "\n";

// Immutability.
$a = Set::of(1, 2);
$b = $a->add(3);
$c = $b->remove(1);
echo count($a), "/", count($b), "/", count($c), "\n";

// Set algebra via methods and operators (sort members for stable output).
function show(Set $s): string { $a = $s->toArray(); sort($a); return implode(",", $a); }
$x = Set::of(1, 2, 3);
$y = Set::of(2, 3, 4);
echo show($x->union($y)), " ", show($x->intersect($y)), " ", show($x->diff($y)), "\n";
echo show($x | $y), " ", show($x & $y), " ", show($x - $y), "\n";

// map / filter.
echo show(Set::of(1, 2, 3, 4)->filter(fn($v) => $v % 2 === 0)), "\n";
echo show(Set::of(1, 2, 3)->map(fn($v) => $v * $v)), "\n";

// String members.
echo show(Set::fromArray(["a", "b", "a", "c"])), "\n";

// Large-N with collisions (mod) collapses to the distinct members.
$big = new Set();
for ($i = 0; $i < 3000; $i++) $big = $big->add($i % 1500);
echo count($big), " ", $big->has(1499) ? "y" : "n", "\n";

// Non int/string member is a type error.
try { Set::of([1]); } catch (\TypeError $e) { echo "type error\n"; }
?>
--EXPECT--
3 yn
2/3/2
1,2,3,4 2,3 1
1,2,3,4 2,3 1
2,4
1,4,9
a,b,c
1500 y
type error
