--TEST--
Tagged template strings — tag"..." calls tag(strings[], values[])
--FILE--
<?php
function tag(array $strings, array $values): string {
    return json_encode($strings) . " :: " . json_encode($values);
}

$x = 42;
$y = "hi";

// Constant pieces and interpolated values are separated.
echo tag"a {$x} b {$y} c", "\n";

// No interpolation: one string piece, no values.
echo tag"plain", "\n";

// Leading, trailing and adjacent interpolations get empty "" separators so that
// count(strings) === count(values) + 1.
$a = 1; $b = 2;
echo tag"{$a}{$b}", "\n";
echo tag"x{$a}", "\n";
echo tag"{$a}x", "\n";

// The built-in `sql` demo tag escapes values.
$id = 5;
$name = "O'Brien";
echo sql"SELECT * FROM users WHERE id = {$id} AND name = {$name}", "\n";
echo sql"vals {$id}, " . "n", "\n";   // composes with normal concatenation
$n = null; $f = 2.5; $t = true;
echo sql"a={$n} b={$f} c={$t}", "\n";

// Ordinary strings and interpolation are unaffected.
echo "normal {$x}", "\n";
?>
--EXPECT--
["a "," b "," c"] :: [42,"hi"]
["plain"] :: []
["","",""] :: [1,2]
["x",""] :: [1]
["","x"] :: [1]
SELECT * FROM users WHERE id = 5 AND name = 'O''Brien'
vals 5, n
a=NULL b=2.5 c=TRUE
normal 42
