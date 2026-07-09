--TEST--
#[Memoize] executable decorator — caches results, recursion included
--FILE--
<?php
// The body executes once per distinct argument, so an exponential recursive
// fib runs linearly (recursion re-enters through the memoized name).
$GLOBALS['calls'] = 0;
#[Memoize]
function fib(int $n): int {
    $GLOBALS['calls']++;
    return $n < 2 ? $n : fib($n - 1) + fib($n - 2);
}
echo fib(30), "\n";
echo $GLOBALS['calls'], "\n";   // 31 distinct n in 0..30, each computed once

// Correctness and per-argument caching.
#[Memoize]
function add(int $a, int $b): int { return $a + $b; }
echo add(1, 2), add(3, 4), add(1, 2), "\n";

// Default and named arguments are forwarded through the wrapper.
#[Memoize]
function g(int $x, int $y = 10): int { return $x - $y; }
echo g(5), "\n";          // 5 - 10
echo g(5, 1), "\n";       // 5 - 1
echo g(y: 1, x: 20), "\n";

// A function without #[Memoize] keeps its exact signature (not wrapped).
function plain(int $a, int $b = 5): int { return $a + $b; }
$r = new ReflectionFunction('plain');
echo $r->getNumberOfParameters(), " ", plain(2), "\n";
?>
--EXPECT--
832040
31
373
-5
4
19
2 7
