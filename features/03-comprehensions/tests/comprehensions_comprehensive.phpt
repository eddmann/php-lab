--TEST--
comprehensions — cartesian, guard, dependent, Option/Result, dict
--FILE--
<?php
// array: cartesian product
print_r(for { $x = [1,2,3]; $y = [10,20] } yield $x + $y);
// guard
print_r(for { $x = range(1,9); if $x % 2 } yield $x * $x);
// dependent generator
print_r(for { $x = [1,2,3]; $y = range(1, $x) } yield "$x:$y");
// Option: all Some
var_dump(for { $x = Some(1); $y = Some(2) } yield $x + $y);
// Option short-circuit
var_dump(for { $x = Some(1); $y = None() } yield $x + $y);
// Result short-circuit on Err
var_dump((for { $x = Ok(1); $y = Err("e") } yield $x + $y)->getError());
// dict comprehension
print_r(for { $x = [1,2,3,4]; if $x % 2 === 0 } yield $x => $x * $x);
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
    [0] => 1
    [1] => 9
    [2] => 25
    [3] => 49
    [4] => 81
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
object(Some)#5 (1) {
  ["value"]=>
  int(3)
}
object(None)#1 (0) {
}
string(1) "e"
Array
(
    [2] => 4
    [4] => 16
)
