--TEST--
placeholder lambdas: bare `_` in a call argument becomes a closure
--FILE--
<?php
// One placeholder -> one-parameter closure.
print_r(array_map(_ * 2, [1, 2, 3]));

// Two placeholders -> two parameters, left to right.
$xs = [3, 1, 2];
usort($xs, _ <=> _);
print_r($xs);

// Predicate position.
print_r(array_filter([-1, 0, 1, 2], _ > 0));

// Repeated placeholder as a binary reducer.
echo array_reduce([1, 2, 3, 4], _ + _, 0), "\n";

// Nested pipeline: each inner `_` binds to its own (innermost) call argument.
print_r(array_map(_ + 1, array_map(_ * 10, [1, 2, 3])));

// The closure captures surrounding variables by value (arrow-fn semantics).
$k = 100;
print_r(array_map(_ + $k, [1, 2]));

// A placeholder may be the receiver of a method call.
class Box {
    public function __construct(public int $v) {}
    public function doubled(): int { return $this->v * 2; }
}
print_r(array_map(_->doubled(), [new Box(5), new Box(6)]));

// A bare `_` as a plain argument becomes the identity closure.
$id = array_map(_, [7, 8]);
print_r($id);
?>
--EXPECT--
Array
(
    [0] => 2
    [1] => 4
    [2] => 6
)
Array
(
    [0] => 1
    [1] => 2
    [2] => 3
)
Array
(
    [2] => 1
    [3] => 2
)
10
Array
(
    [0] => 11
    [1] => 21
    [2] => 31
)
Array
(
    [0] => 101
    [1] => 102
)
Array
(
    [0] => 10
    [1] => 12
)
Array
(
    [0] => 7
    [1] => 8
)
