--TEST--
unless: Ruby-style negated-if construct (block, else, and statement modifier)
--FILE--
<?php
// Block form: body runs only when the condition is false.
unless (false) {
    echo "block-runs\n";
}
unless (true) {
    echo "should-not-print\n";
}

// else clause.
unless (true) {
    echo "should-not-print\n";
} else {
    echo "else-runs\n";
}
unless (false) {
    echo "body-runs\n";
} else {
    echo "should-not-print\n";
}

// Statement modifier: `STMT unless COND;`.
print "modifier-runs\n" unless false;
print "should-not-print\n" unless true;

// Equivalent to if (!cond) across values.
foreach ([1, 0, -1] as $x) {
    unless ($x > 0) {
        echo "nonpos:$x\n";
    }
}

// Semi-reserved: still usable as a method/const name.
class C {
    const unless = "const-ok";
    function unless() { return "method-ok"; }
}
echo (new C)->unless(), "\n";
echo C::unless, "\n";

// Exposed to the tokenizer.
var_dump(in_array(
    "T_UNLESS",
    array_map("token_name", array_column(token_get_all("<?php unless(true){}"), 0)),
    true
));
var_dump(defined("T_UNLESS"));
?>
--EXPECT--
block-runs
else-runs
body-runs
modifier-runs
nonpos:0
nonpos:-1
method-ok
const-ok
bool(true)
bool(true)
