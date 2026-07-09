--TEST--
val — write-once local bindings (read, scope isolation, semi-reserved name)
--FILE--
<?php
// A val reads back like any local.
val $x = 5;
echo $x, "\n";

// Usable in expressions and passed by value.
function dbl(int $n): int { return $n * 2; }
val $y = 21;
echo dbl($y) + $y, "\n";

// A val array can be iterated (read) as a foreach source.
val $xs = [1, 2, 3];
$sum = 0;
foreach ($xs as $v) { $sum += $v; }
echo $sum, "\n";

// val-ness is per function scope: the same name is independent across functions,
// and a plain variable elsewhere is unaffected.
function a() { val $v = 10; return $v; }
function b() { val $v = 20; return $v; }
echo a(), ",", b(), "\n";

function plain() { $v = 1; $v = 2; return $v; }   // not a val — reassignable
echo plain(), "\n";

// Closures get their own scope.
val $z = 1;
$fn = function () { $z = 99; return $z; };
echo $z, ",", $fn(), "\n";

// `val` is only semi-reserved: still usable as a method and constant name.
class C {
    const val = 42;
    public function val(int $n): string { return "m$n"; }
}
echo (new C)->val(7), "\n";
echo C::val, "\n";
?>
--EXPECT--
5
63
6
10,20
2
1,99
m7
42
