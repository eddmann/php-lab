--TEST--
refinements — class override, add, subclass, fallback
--FILE--
<?php
class Money { public function __construct(public int $cents) {} function fmt(): string { return '$' . $this->cents; } }
refinement Eu for Money {
    function fmt(): string { return $this->cents . ' EUR'; }        // override
    function half(): Money { return new Money((int)($this->cents / 2)); } // add
}
using Eu;
$m = new Money(1000);
echo $m->fmt(), "\n";                    // overridden: "1000 EUR"
echo $m->half()->fmt(), "\n";            // added + overridden: "500 EUR"
// subclass application
class Cash extends Money {}
echo (new Cash(200))->fmt(), "\n";       // "200 EUR"
// fallback to normal method for unrefined calls
echo strlen("plain"), "\n";
--EXPECT--
1000 EUR
500 EUR
200 EUR
5
