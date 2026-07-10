--TEST--
lazy variadic parameter is a compile error
--FILE--
<?php
function f(lazy ...$x) {}
?>
--EXPECTF--
Fatal error: Lazy parameter $x cannot be variadic in %s on line %d
