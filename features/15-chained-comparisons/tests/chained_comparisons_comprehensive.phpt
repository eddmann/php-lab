--TEST--
chained comparisons: equality tier untouched, floats/strings, side effects, nesting
--FILE--
<?php
// Non-breaking: the equality tier is still non-associative (a parse error),
// and mixed precedence keeps its meaning: `1 < 2 == true` is `(1 < 2) == true`.
var_dump(1 < 2 == true);      // true

// Floats and strings compare as usual, just chained.
var_dump(0.5 < 1.5 < 2.5);    // true
var_dump("a" < "b" < "c");    // true

// Descending chain with >= and >.
$n = 7;
var_dump(10 >= $n > 0);       // true

// Each operand evaluated once, left to right; a failing early link stops the rest.
$log = [];
$f = function (string $tag, int $v) use (&$log) { $log[] = $tag; return $v; };
$r = $f('a', 1) < $f('b', 9) < $f('c', 5) < $f('d', 100);
var_dump($r);                 // 1<9 true, 9<5 false -> false; 'd' never evaluated
echo implode(',', $log), "\n";// a,b,c

// A chain used inside a larger boolean expression.
$x = 5;
var_dump(($x > 0) && (0 < $x < 10));   // true

// Guard-style usage.
function clamp(int $v): string {
    return 0 <= $v < 100 ? "in range" : "out";
}
echo clamp(50), "\n";
echo clamp(150), "\n";
echo clamp(-1), "\n";
?>
--EXPECT--
bool(true)
bool(true)
bool(true)
bool(true)
bool(false)
a,b,c
bool(true)
in range
out
out
