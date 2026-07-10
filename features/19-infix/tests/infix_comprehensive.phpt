--TEST--
infix calls: namespaces, precedence detail, errors, non-interference
--FILE--
<?php
namespace App;

// A namespaced function wins over a global one; \max falls back to global.
function dist(int $a, int $b): int { return \abs($a - $b); }
echo 3 dist 10, "\n";            // App\dist
echo 3 max 10, "\n";             // global \max

// Tighter than string concatenation.
echo "n=" . 2 max 9, "\n";       // "n=" . max(2, 9)

// Works inside larger expressions and call arguments.
echo \intdiv(20 max 30, 3), "\n";           // intdiv(30, 3)
$xs = [5 min 2, 5 max 2];
echo \implode(",", $xs), "\n";

// Undefined function gives the usual error.
try {
    $r = 1 nosuchfn 2;
} catch (\Error $e) {
    echo $e->getMessage(), "\n";
}

// Non-interference: constants still work in expressions.
const WIDTH = 10;
echo WIDTH + 1, "\n";
echo \PHP_INT_SIZE >= 4 ? "ok" : "?", "\n";

// A parenthesised call is still a call, never an infix operand mixup.
function double(int $n): int { return $n * 2; }
echo double(4) max double(2), "\n";          // max(8, 4)
?>
--EXPECT--
7
10
n=9
10
2,5
Call to undefined function App\nosuchfn()
11
ok
8
