--TEST--
lazy params: closures, degradation, read-only, validations, methods
--FILE--
<?php
// A closure in the body captures the thunk; memoization is shared.
function count_evals(): int { static $n = 0; $n++; echo "eval#$n "; return 7; }
function h(lazy $x): int { $g = fn() => $x + 1; return $g() + $x; }
echo h(count_evals()), "\n";                    // eval#1 once, 8 + 7 = 15

// Dynamic and indirect calls degrade to eager evaluation but stay correct.
function shout(lazy $s): string { return strtoupper($s); }
$fn = "shout";
echo $fn("dynamic"), "\n";
echo call_user_func("shout", "cuf"), "\n";

// A lazy parameter is read-only.
try {
    eval('function w(lazy $x) { $x = 5; }');
} catch (\Throwable $e) {
    echo "unreachable\n";
}
?>
--EXPECTF--
eval#1 15
DYNAMIC
CUF

Fatal error: Cannot modify lazy parameter $x in %s on line %d
