--TEST--
Propagated `?` value is checked against the enclosing return type
--FILE--
<?php
// Short-circuit returns a None, which is incompatible with `: int`.
function bad(): int {
    $x = None()?;
    return $x;
}
try {
    bad();
} catch (\TypeError $e) {
    echo "TypeError\n";
}

// Compatible return type works.
function good(): Option {
    $x = None()?;
    return Some($x);
}
var_dump(good() instanceof None);
?>
--EXPECT--
TypeError
bool(true)
