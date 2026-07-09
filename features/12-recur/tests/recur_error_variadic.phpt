--TEST--
recur in a variadic function is a compile error
--FILE--
<?php
function f(int ...$n) { return recur(1); }
?>
--EXPECTF--
Fatal error: recur cannot be used in a variadic function in %s on line %d
