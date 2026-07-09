--TEST--
match destructuring patterns for built-in Option/Result
--FILE--
<?php
// Binding: Some($x) / Ok($v) / Err($e) bind the unwrapped payload.
echo match (Some(5)) { Some($x) => $x * 2, None => -1 }, "\n";       // 10
echo match (None())   { Some($x) => $x,     None => "none" }, "\n";   // none
echo match (Ok(7))    { Ok($v) => $v,      Err($e) => "e:$e" }, "\n"; // 7
echo match (Err("bad")) { Ok($v) => $v,    Err($e) => "e:$e" }, "\n"; // e:bad

// default arm.
echo match (Some(1)) { None => "n", default => "d" }, "\n";          // d

// No arm matches and no default => UnhandledMatchError.
try {
    match (Some(1)) { None => "n" };
} catch (\UnhandledMatchError $e) {
    echo "unhandled\n";
}

// Plain match (no patterns) is unaffected.
echo match (2) { 1 => "a", 2 => "b", default => "c" }, "\n";          // b

// Nested: a pattern body may itself be a match.
echo match (Ok(3)) {
    Ok($v)  => match ($v) { 3 => "three", default => "?" },
    Err($e) => "err",
}, "\n";                                                              // three
?>
--EXPECT--
10
none
7
e:bad
d
unhandled
b
three
