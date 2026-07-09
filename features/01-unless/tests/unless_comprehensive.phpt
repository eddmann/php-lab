--TEST--
unless — block, else, modifier, truthiness, nesting, semi-reserved
--FILE--
<?php
// block form
unless (false) { echo "runs when false\n"; }
unless (true)  { echo "should NOT run\n"; }
// else
unless (in_array(3, [1,2])) { echo "no 3\n"; } else { echo "has 3\n"; }
unless (in_array(2, [1,2])) { echo "no 2\n"; } else { echo "has 2\n"; }
// statement modifier
$dry = false;
print "saved\n" unless $dry;
$dry = true;
print "not-printed-when-dry\n" unless $dry;
// truthiness parity with if(!...)
unless (0) { echo "0 is falsy\n"; }
unless ("") { echo "empty string falsy\n"; }
unless ([]) { echo "empty array falsy\n"; }
unless ("0") { echo "string zero is falsy too\n"; }
// nesting + short-circuit
function side(): bool { echo "side()\n"; return true; }
unless (true && side()) { echo "no\n"; }   // side() runs
unless (false) { unless (false) { echo "nested\n"; } }
// semi-reserved: usable as a method/const name
class C { const unless = 42; function unless() { return "method"; } }
echo C::unless, "\n";
echo (new C)->unless(), "\n";
--EXPECT--
runs when false
no 3
has 2
saved
0 is falsy
empty string falsy
empty array falsy
string zero is falsy too
side()
nested
42
method
