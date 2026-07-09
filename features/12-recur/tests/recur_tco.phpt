--TEST--
Automatic self-TCO: plain tail self-calls are eliminated; dispatch and semantics preserved
--FILE--
<?php
// `return f(...)` inside plain function f — no keyword, constant stack.
function fact_iter(int $n, int $acc) {
    if ($n <= 1) return $acc;
    return fact_iter($n - 1, $acc + $n);
}
var_dump(fact_iter(200000, 0));

// Non-tail recursion is untouched (result feeds an addition).
function fib(int $n): int {
    return $n < 2 ? $n : fib($n - 1) + fib($n - 2);
}
echo fib(20), "\n";

// Methods never auto-TCO: subclass override keeps dynamic dispatch.
class A {
    public function f(int $n): string {
        if ($n === 0) return "A";
        return $this->f($n - 1);
    }
}
class B extends A {
    public function f(int $n): string { return "B"; }
}
echo (new B)->f(3), "\n";
echo (new A)->f(3), "\n";

// Arg-count mismatch (defaults in play) falls back to an ordinary call.
function g(int $n, int $acc = 0) {
    if ($n === 0) return $acc;
    return g($n - 1);              // 1 arg for 2 params -> normal call
}
echo g(5), "\n";

// Mutual recursion is ordinary calls (only SELF tail calls are eliminated).
function is_even(int $n): bool { return $n === 0 ? true : is_odd($n - 1); }
function is_odd(int $n): bool  { return $n === 0 ? false : is_even($n - 1); }
var_dump(is_even(10000));

// Case-insensitive function names still match.
function upper(int $n, int $acc) {
    if ($n === 0) return $acc;
    return UPPER($n - 1, $acc + 1);
}
echo upper(200000, 0), "\n";
?>
--EXPECT--
int(20000099999)
6765
B
A
0
bool(true)
200000
