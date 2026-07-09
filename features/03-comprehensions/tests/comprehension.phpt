--TEST--
for {} yield comprehensions — unified over arrays and Option/Result
--FILE--
<?php
// Arrays: cartesian product.
print_r(for { $x = [1, 2, 3]; $y = [10, 20] } yield $x + $y);

// Arrays: single generator is a map.
print_r(for { $x = [1, 2, 3] } yield $x * 2);

// Arrays: guard filters.
print_r(for { $x = [1, 2, 3, 4]; if $x % 2 === 0 } yield $x * $x);

// Arrays: a later generator may depend on an earlier binding.
print_r(for { $x = [1, 2, 3]; $y = range(1, $x) } yield "$x:$y");

// Option: all-Some binds; a None short-circuits.
echo for { $x = Some(1); $y = Some(2) } yield $x + $y, "\n";
echo for { $x = Some(1); $y = None() } yield $x + $y, "\n";

// Result: Ok chains; an Err short-circuits to that Err.
echo for { $x = Ok(4); $y = Ok(5) } yield $x * $y, "\n";
echo for { $x = Ok(1); $y = Err("boom") } yield $x + $y, "\n";

// A guard on an Option uses its filter() -> can turn Some into None.
echo for { $x = Some(4); if $x > 10 } yield $x, "\n";

// Mixing array and Option in one comprehension is an error.
try {
    for { $x = [1, 2]; $y = Some(3) } yield $x + $y;
} catch (\Error $e) {
    echo "type error\n";
}
?>
--EXPECT--
Array
(
    [0] => 11
    [1] => 21
    [2] => 12
    [3] => 22
    [4] => 13
    [5] => 23
)
Array
(
    [0] => 2
    [1] => 4
    [2] => 6
)
Array
(
    [0] => 4
    [1] => 16
)
Array
(
    [0] => 1:1
    [1] => 2:1
    [2] => 2:2
    [3] => 3:1
    [4] => 3:2
    [5] => 3:3
)
Some(3)
None
Ok(20)
Err("boom")
None
type error
