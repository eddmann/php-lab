--TEST--
lazy parameter with a default value is a compile error
--FILE--
<?php
function f(lazy $x = 1) {}
?>
--EXPECTF--
Fatal error: Lazy parameter $x cannot have a default value in %s on line %d
