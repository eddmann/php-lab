--TEST--
trailing closures: nesting, hooks intact, return semantics, generators, FCC guard
--FILE--
<?php
// Nested trailing blocks.
function wrap(callable $f) { return $f(); }
echo wrap() { return wrap() { return "nested"; }; }, "\n";

// `return` returns from the BLOCK (Swift semantics), not the enclosing function.
function outer(): string {
    $r = wrap() { return "inner"; };
    return "outer:$r";
}
echo outer(), "\n";

// The block may follow other arguments, including literals and variables.
function fold(array $xs, int $init, callable $f): int {
    $acc = $init;
    foreach ($xs as $x) { $acc = $f($acc, $x); }
    return $acc;
}
echo fold([1, 2, 3], 100) { |$acc, $x| return $acc + $x; }, "\n";

// Property hooks (the other meaning of `expr {`) still parse and run.
class P {
    public int $x = 5 { get => $this->x * 2; }
}
echo (new P)->x, "\n";

// yield inside a block makes the block (not the caller) a generator.
function collect(callable $f): array { return iterator_to_array($f()); }
print_r(collect() { yield 1; yield 2; });

// Normal anonymous functions and blocks are unaffected.
$f = function () { return "fn"; };
if (true) { echo $f(), "\n"; }

// First-class callable syntax cannot take a trailing closure (compile error).
eval('strlen(...) { return 1; };');
?>
--EXPECTF--
nested
outer:inner
106
10
Array
(
    [0] => 1
    [1] => 2
)
fn

Fatal error: Cannot combine first-class callable syntax with a trailing closure in %s on line %d
