--TEST--
`??` unwraps Some/Ok and falls back on None/Err (Propagatable-aware coalesce)
--FILE--
<?php
var_dump(Some(5) ?? 0);      // unwraps
var_dump(None() ?? 0);       // falls back
var_dump(Ok(7) ?? -1);       // unwraps
var_dump(Err("x") ?? -1);    // falls back

// Plain values and null are unchanged.
var_dump(42 ?? 0);
var_dump(null ?? "d");

// The default is lazy: not evaluated when the left side is present.
function boom() { throw new \Exception("should not run"); }
var_dump(Some("ok") ?? boom());

// Chaining works left-to-right; first present-and-success wins.
var_dump(None() ?? None() ?? "last");
var_dump(None() ?? Some("mid") ?? "last");
?>
--EXPECT--
int(5)
int(0)
int(7)
int(-1)
int(42)
string(1) "d"
string(2) "ok"
string(4) "last"
string(3) "mid"
