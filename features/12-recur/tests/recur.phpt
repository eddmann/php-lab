--TEST--
recur — explicit tail recursion: constant stack, swap safety, methods, closures, loops
--FILE--
<?php
// Deep explicit recursion: would exhaust memory as ordinary recursion.
function sum_to(int $n, int $acc) {
    if ($n === 0) return $acc;
    return recur($n - 1, $acc + $n);
}
var_dump(sum_to(200000, 0));

// Bare-statement form.
function count_down(int $n) {
    if ($n === 0) { echo "liftoff\n"; return; }
    recur($n - 1);
}
count_down(100000);

// Arguments are evaluated before rebinding: swaps read pre-rebind values.
function swap_n(int $a, int $b, int $n) {
    if ($n === 0) return "$a,$b";
    return recur($b, $a, $n - 1);
}
echo swap_n(1, 2, 5), "\n";
echo swap_n(1, 2, 4), "\n";

// Methods and closures may recur explicitly.
class Counter {
    public function sum(int $n, int $acc): int {
        if ($n === 0) return $acc;
        return recur($n - 1, $acc + $n);
    }
}
echo (new Counter)->sum(100000, 0), "\n";

$sum = function (int $n, int $acc) {
    if ($n === 0) return $acc;
    return recur($n - 1, $acc + $n);
};
echo $sum(100000, 0), "\n";

// recur out of a foreach frees the loop iterator each round.
function scan(array $xs, int $depth = 0) {
    foreach ($xs as $x) {
        if ($x === 99) return "found";
    }
    if ($depth >= 50000) return "done@$depth";
    return recur($xs, $depth + 1);
}
echo scan([1, 2, 3]), "\n";

// Default-parameter functions: recur passes a value for every declared param.
function step(int $n, int $acc = 0) {
    if ($n === 0) return $acc;
    return recur($n - 1, $acc + 1);
}
echo step(100000), "\n";
?>
--EXPECT--
int(20000100000)
liftoff
2,1
1,2
5000050000
5000050000
done@50000
100000
