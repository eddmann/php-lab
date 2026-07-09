--TEST--
`instanceof Some($x)` binds the unwrapped payload (if-let style)
--FILE--
<?php
// Bind on a true test; the expression still evaluates to the bool.
if (Some(7) instanceof Some($x)) {
    echo "some: $x\n";
} else {
    echo "none\n";
}

if (None() instanceof Some($x)) {
    echo "some\n";
} else {
    echo "else\n";
}

// Ok binds getValue(), Err binds getError().
$r = Err("boom");
if ($r instanceof Ok($v)) {
    echo "ok: $v\n";
} elseif ($r instanceof Err($e)) {
    echo "err: $e\n";
}

// while-let: drain a list of Options.
$xs = [Some(1), Some(2), None()];
while (array_shift($xs) instanceof Some($v)) {
    echo "drain $v\n";
}

// The whole thing is a boolean expression.
var_dump(Some(1) instanceof Some($a));
var_dump(None() instanceof Some($b));

// Plain instanceof (no capture) is unchanged.
var_dump(Some(1) instanceof Some);
var_dump(Some(1) instanceof None);
?>
--EXPECT--
some: 7
else
err: boom
drain 1
drain 2
bool(true)
bool(false)
bool(true)
bool(false)
