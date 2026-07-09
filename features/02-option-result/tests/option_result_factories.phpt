--TEST--
Option/Result: try, fromNullable, all (sequence), and __toString
--FILE--
<?php
// Result::try captures the return value or any thrown Throwable.
var_dump(Result::try(fn() => 21 * 2)->unwrap());                       // int(42)
echo Result::try(fn() => throw new RuntimeException("boom"))
        ->getError()->getMessage(), "\n";                              // boom

// Option::fromNullable bridges PHP's null world.
var_dump(Option::fromNullable(null) instanceof None);                  // true
echo Option::fromNullable(7)->unwrap(), "\n";                          // 7

// Sequence: all present -> wrapper of the list (keys preserved); else short-circuit.
print_r(Option::all([Some(1), Some(2), Some(3)])->unwrap());           // [1,2,3]
var_dump(Option::all([Some(1), None(), Some(3)]) instanceof None);     // true
print_r(Result::all(['a' => Ok(1), 'b' => Ok(2)])->unwrap());          // [a=>1,b=>2]
echo Result::all([Ok(1), Err("e2"), Err("e3")])->getError(), "\n";    // e2

// __toString / Stringable.
echo (string) Some(42), "\n";                                         // Some(42)
echo (string) None(), "\n";                                           // None
echo (string) Ok("hi"), "\n";                                         // Ok("hi")
echo "result is " . Err("bad"), "\n";                                 // result is Err("bad")
?>
--EXPECT--
int(42)
boom
bool(true)
7
Array
(
    [0] => 1
    [1] => 2
    [2] => 3
)
bool(true)
Array
(
    [a] => 1
    [b] => 2
)
e2
Some(42)
None
Ok("hi")
result is Err("bad")
