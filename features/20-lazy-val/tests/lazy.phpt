--TEST--
lazy: locals are computed on first read and then memoised
--FILE--
<?php
// Deferred: the initializer runs only on first read.
function make(): int { echo "computing\n"; return 42; }
lazy $x = make();
echo "before\n";
echo $x, "\n";       // computing, then 42
echo $x, "\n";       // 42 (memoised, no recompute)

// Never read -> never computed.
lazy $unused = (function () { echo "NEVER\n"; return 0; })();
echo "after\n";

// Captures the surrounding scope by value at the declaration point.
$a = 10;
lazy $snap = $a + 1;
$a = 999;
echo $snap, "\n";    // 11

// A lazy initializer may force an earlier lazy.
lazy $base = (function () { echo "base\n"; return 5; })();
lazy $doubled = $base * 2;
echo "mark\n";
echo $doubled, "\n"; // base, then 10

// Reads work in every context.
lazy $n = 21;
echo "interp=$n\n";                 // interpolation
echo [$n, $n * 2][1], "\n";         // array + arithmetic
echo ($n > 0 ? "pos" : "neg"), "\n"; // ternary

// A lazy holding an object; method calls force then dispatch.
lazy $o = new ArrayObject([1, 2, 3]);
echo $o->count(), "\n";             // 3
?>
--EXPECT--
before
computing
42
42
after
11
mark
base
10
interp=21
42
pos
3
