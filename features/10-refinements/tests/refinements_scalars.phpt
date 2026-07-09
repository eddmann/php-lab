--TEST--
refinements — scalars, arrays, ints, non-refined class default
--FILE--
<?php
refinement Str for string {
    function shout(): string { return strtoupper($this) . '!'; }
    function repeatN(int $n): string { return str_repeat($this, $n); }
}
refinement Arr for array {
    function sum(): int { return array_sum($this); }
}
refinement Num for int {
    function double(): int { return $this * 2; }
}
using Str;
using Arr;
using Num;
echo "hi"->shout(), "\n";           // scalar string
echo "ab"->repeatN(3), "\n";
echo [1,2,3,4]->sum(), "\n";        // array
echo (21)->double(), "\n";            // int
// override + add on a class, scoped
class Money { public function __construct(public int $cents) {} function fmt() { return '$' . $this->cents; } }
$m = new Money(1000);
echo $m->fmt(), "\n";               // default here (no refinement active for Money yet in this scope? it is via file)
--EXPECT--
HI!
ababab
10
42
$1000
