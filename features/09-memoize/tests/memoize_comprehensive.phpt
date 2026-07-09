--TEST--
Memoize — caching, recursion, arg keying, defaults/named, untouched
--FILE--
<?php
$calls = 0;
#[Memoize]
function slow(int $n): int { global $calls; $calls++; return $n * 2; }
echo slow(5), " ", slow(5), " ", slow(5), "\n"; // body once
echo "calls=$calls\n";
// recursion runs linearly
$fibcalls = 0;
#[Memoize]
function fib(int $n): int { global $fibcalls; $fibcalls++; return $n < 2 ? $n : fib($n-1) + fib($n-2); }
echo fib(30), "\n";
echo "fib bodies=$fibcalls\n"; // ~31, not exponential
// distinct args cached separately
#[Memoize]
function add(int $a, int $b): int { return $a + $b; }
echo add(1,2), " ", add(2,1), " ", add(1,2), "\n";
// default + named args forwarded
#[Memoize]
function greet(string $who, string $sep = ": "): string { return "hi{$sep}{$who}"; }
echo greet("a"), " | ", greet("b", sep: "! "), "\n";
// non-memoized functions untouched
function plain(int $n): int { return $n; }
echo plain(7), "\n";
--EXPECT--
10 10 10
calls=1
832040
fib bodies=31
3 3 3
hi: a | hi! b
7
