--TEST--
chained comparisons: a < b < c means a < b && b < c (Python-style)
--FILE--
<?php
// Basic chains.
var_dump(1 < 2 < 3);          // true
var_dump(3 < 2 < 1);          // false  (3 < 2 fails)
var_dump(1 < 5 < 3);          // false  (5 < 3 fails)
var_dump(0 <= 5 < 10);        // true
var_dump(10 > 5 > 1);         // true
var_dump(1 <= 1 <= 1);        // true

// Mixing relational operators within a chain.
var_dump(5 > 3 < 4);          // true   (5 > 3 && 3 < 4)

// The range idiom.
$i = 5;
var_dump(0 <= $i < 10);       // true
$i = 15;
var_dump(0 <= $i < 10);       // false

// A four-operand chain.
var_dump(1 < 2 < 3 < 4);      // true
var_dump(1 < 2 < 9 < 4);      // false

// The middle operand is evaluated exactly once.
function mid(): int { echo "mid\n"; return 5; }
var_dump(0 < mid() < 10);     // prints "mid" once, then true

// Short-circuit: once a link fails, the rest is not evaluated.
function rhs(): int { echo "rhs\n"; return 100; }
var_dump(5 < 2 < rhs());      // 5 < 2 fails -> rhs() never called -> false
?>
--EXPECT--
bool(true)
bool(false)
bool(false)
bool(true)
bool(true)
bool(true)
bool(true)
bool(true)
bool(false)
bool(true)
bool(false)
mid
bool(true)
bool(false)
