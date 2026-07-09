--TEST--
Option/Result combinators: flatMap, filter, orElse, unwrap, expect, mapErr, conversions
--FILE--
<?php
// Option
echo Some(3)->flatMap(fn($x) => Some($x + 5))->unwrap(), "\n";          // 8
echo None()->flatMap(fn($x) => Some($x))->unwrapOr("n"), "\n";          // n
echo Some(4)->filter(fn($x) => $x % 2 === 0)->unwrapOr("no"), "\n";     // 4
echo Some(3)->filter(fn($x) => $x % 2 === 0)->unwrapOr("no"), "\n";     // no
echo None()->orElse(fn() => Some(9))->unwrap(), "\n";                   // 9
echo Some(7)->toNullable(), "\n";                                       // 7
var_dump(None()->toNullable());                                         // NULL

// Option -> Result
echo match (Some(1)->okOr("e")) { Ok($v) => "ok:$v", Err($x) => "err:$x" }, "\n";  // ok:1
echo match (None()->okOr("e"))  { Ok($v) => "ok:$v", Err($x) => "err:$x" }, "\n";  // err:e

// Result
echo Ok(10)->flatMap(fn($x) => Ok($x * 2))->unwrap(), "\n";             // 20
echo Err("bad")->flatMap(fn($x) => Ok($x))->unwrapOr("kept"), "\n";     // kept
echo Err("bad")->mapErr(fn($e) => strtoupper($e))->getError(), "\n";   // BAD
echo Err("x")->orElse(fn($e) => Ok("recovered"))->unwrap(), "\n";      // recovered
echo Ok(5)->toOption()->unwrap(), "\n";                                 // 5
var_dump(Err("x")->toNullable());                                       // NULL

// expect() throws with the given message on the empty side.
try {
    None()->expect("no value");
} catch (\Error $e) {
    echo $e->getMessage(), "\n";                                        // no value
}

// flatMap enforces that the callback returns the right wrapper.
try {
    Some(1)->flatMap(fn($x) => $x);
} catch (\Error $e) {
    echo "type-guarded\n";
}

// A fluent pipeline.
echo Some(2)
    ->map(fn($x) => $x * 3)
    ->filter(fn($x) => $x > 5)
    ->unwrapOr("none"), "\n";                                           // 6
?>
--EXPECT--
8
n
4
no
9
7
NULL
ok:1
err:e
20
kept
BAD
recovered
5
NULL
no value
type-guarded
6
