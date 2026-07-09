--TEST--
Tagged templates — parts/values split, escaping, edge holes, non-string return
--FILE--
<?php
// A debug tag: shows how the parser splits constant pieces vs interpolated values.
function dbg(array $strings, array $values): string {
    return json_encode($strings) . " | " . json_encode($values)
         . " | invariant=" . (count($strings) === count($values) + 1 ? "ok" : "BROKEN");
}
$a = 1; $b = 2;
echo dbg"start {$a} mid {$b} end", "\n";
echo dbg"{$a}{$b}", "\n";        // adjacent -> ["","",""]
echo dbg"x{$a}", "\n";           // trailing empty
echo dbg"{$a}x", "\n";           // leading empty
echo dbg"plain", "\n";           // no interpolation
echo dbg"nohole", "\n";

// The built-in sql demo tag: escaping and literal rendering.
$id = 5; $name = "O'Brien"; $n = null; $pi = 3.5;
echo sql"SELECT * FROM users WHERE id = {$id} AND name = {$name}", "\n";
echo sql"types: {$pi}, {$n}, {true}, {false}", "\n";
echo sql"escape: {$name} twice {$name}", "\n";

// A tag can return any type, not just a string.
function pair(array $s, array $v): array { return [$s, $v]; }
var_dump(pair"a{$a}b");
--EXPECT--
["start "," mid "," end"] | [1,2] | invariant=ok
["","",""] | [1,2] | invariant=ok
["x",""] | [1] | invariant=ok
["","x"] | [1] | invariant=ok
["plain"] | [] | invariant=ok
["nohole"] | [] | invariant=ok
SELECT * FROM users WHERE id = 5 AND name = 'O''Brien'
types: 3.5, NULL, {true}, {false}
escape: 'O''Brien' twice 'O''Brien'
array(2) {
  [0]=>
  array(2) {
    [0]=>
    string(1) "a"
    [1]=>
    string(1) "b"
  }
  [1]=>
  array(1) {
    [0]=>
    int(1)
  }
}
