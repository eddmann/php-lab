--TEST--
infix calls: a bare identifier between two expressions calls the like-named function
--FILE--
<?php
// Any two-argument function works infix — including internal ones.
echo 3 max 7, "\n";              // max(3, 7)
echo 3 min 7, "\n";              // min(3, 7)
echo 2 pow 10, "\n";             // pow(2, 10)
var_dump("hello" str_contains "ell");

// User-defined functions.
function add(int $a, int $b): int { return $a + $b; }
echo 1 add 2, "\n";

// Left-associative: add(add(1, 2), 3).
echo 1 add 2 add 3, "\n";

// Binds tighter than arithmetic and comparison.
echo 2 max 3 * 10, "\n";         // max(2,3) * 10
echo 10 - 2 max 5, "\n";         // 10 - max(2,5)
var_dump(1 max 5 > 3);           // max(1,5) > 3

// Any expression can be an operand.
function clamp(int $x, array $range): int { return max($range[0], min($range[1], $x)); }
echo 150 clamp [0, 100], "\n";
echo -5 clamp [0, 100], "\n";
$speed = 42;
echo $speed clamp [0, 40], "\n";

// Normal call syntax for the same functions is unchanged.
echo max(3, 7), " ", add(1, 2), "\n";
?>
--EXPECT--
7
3
1024
bool(true)
3
6
30
5
bool(true)
100
0
40
7 3
