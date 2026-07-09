--TEST--
Postfix `?` propagation operator with built-in Option/Result
--FILE--
<?php
// None short-circuits the enclosing function.
function firstNone(): Option {
    $x = None()?;          // returns None here
    echo "unreachable\n";
    return Some($x);
}
var_dump(firstNone() instanceof None);

// Some unwraps to the inner value.
function addOne(): Option {
    $x = Some(41)?;
    return Some($x + 1);
}
var_dump(addOne()->value);

// Result: Ok unwraps, Err propagates.
function okPath(): Result {
    $x = Ok(10)?;
    return Ok($x * 2);
}
function errPath(): Result {
    $x = Err("boom")?;
    return Ok($x);
}
var_dump(okPath()->value);
$e = errPath();
var_dump($e instanceof Err, $e->getError());

// Chaining: first failure wins.
function chain(): Option {
    $a = Some(2)?;
    $b = None()?;
    return Some($a + $b);
}
var_dump(chain() instanceof None);

// finally runs on short-circuit.
function withFinally(): Option {
    try {
        $x = None()?;
        return Some($x);
    } finally {
        echo "finally\n";
    }
}
var_dump(withFinally() instanceof None);

// Ternary / coalesce still parse and run normally.
$t = true ? "y" : "n";
$c = null ?? "d";
echo $t, $c, "\n";
?>
--EXPECT--
bool(true)
int(42)
int(20)
bool(true)
string(4) "boom"
bool(true)
finally
bool(true)
yd
