--TEST--
refinements — scoped extension/override methods (scalars, arrays, classes)
--FILE--
<?php
refinement Str for string {
    function shout(): string { return strtoupper($this) . '!'; }
}
refinement Num for int {
    function plus(int $n): int { return $this + $n; }
}
refinement Arr for array {
    function total(): int { return array_sum($this); }
}

class Money {
    public function __construct(public int $cents) {}
    public function fmt(): string { return '$' . $this->cents; }
}
refinement Eu for Money {
    function fmt(): string { return $this->cents . ' EUR'; }   // override
    function half(): Money { return new Money((int)($this->cents / 2)); } // add
}

using Str;
using Num;
using Arr;
using Eu;

// Scalars and arrays gain methods.
echo "hi"->shout(), "\n";
echo (40)->plus(2), "\n";
echo [1, 2, 3, 4]->total(), "\n";

// Add a new method to a class, and override an existing one.
$m = new Money(1000);
echo $m->fmt(), "\n";            // overridden -> "1000 EUR"
echo $m->half()->cents, "\n";   // added -> 500

// A normal call with no matching refinement falls through to the real method.
class Plain { public function hi(): string { return 'hello'; } }
echo (new Plain)->hi(), "\n";

// Visibility is preserved on the fallback path (private call still works).
class WithPriv {
    private function secret(): string { return 'shh'; }
    public function run(): string { return $this->secret(); }
}
echo (new WithPriv)->run(), "\n";
?>
--EXPECT--
HI!
42
10
1000 EUR
500
hello
shh
