--TEST--
placeholder lambdas: boundaries, predicates, named args, nesting, non-interference
--FILE--
<?php
// Larger operator expression with one placeholder (the whole argument is the body).
print_r(array_map(_ * 3 + 1, [1, 2, 3]));

// Boolean predicate.
print_r(array_values(array_filter(range(1, 6), _ % 2 === 0)));

// Two placeholders form a comparator.
$xs = [5, 2, 8, 1];
usort($xs, _ <=> _);
echo implode(',', $xs), "\n";

// Nested calls: inner `_` binds to the inner call, outer to the outer.
print_r(array_map(_ - 1, array_map(_ * 10, [1, 2, 3])));

// Placeholder inside a named argument.
function twice(callable $f, int $x): int { return $f($f($x)); }
echo twice(f: _ + 5, x: 0), "\n";

// A user function literally named `_` is a call, not a placeholder.
function _(string $s): string { return "[$s]"; }
echo _("x"), "\n";

// Placeholder as a method-call receiver.
class Money {
    public function __construct(public int $cents) {}
    public function dollars(): float { return $this->cents / 100; }
}
print_r(array_map(_->dollars(), [new Money(150), new Money(250)]));

// Ternary body.
print_r(array_map(_ > 0 ? "pos" : "nonpos", [-1, 0, 3]));
?>
--EXPECT--
Array
(
    [0] => 4
    [1] => 7
    [2] => 10
)
Array
(
    [0] => 2
    [1] => 4
    [2] => 6
)
1,2,5,8
Array
(
    [0] => 9
    [1] => 19
    [2] => 29
)
10
[x]
Array
(
    [0] => 1.5
    [1] => 2.5
)
Array
(
    [0] => nonpos
    [1] => nonpos
    [2] => pos
)
