--TEST--
lazy parameter passed by reference is a compile error
--FILE--
<?php
function f(lazy &$x) {}
?>
--EXPECTF--
Fatal error: Lazy parameter $x cannot be passed by reference in %s on line %d
