--TEST--
lazy parameter with a declared type is a compile error
--FILE--
<?php
function f(lazy int $x) {}
?>
--EXPECTF--
Fatal error: Lazy parameter $x cannot declare a type in %s on line %d
