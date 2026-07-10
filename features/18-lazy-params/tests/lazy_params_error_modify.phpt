--TEST--
writing to a lazy parameter is a compile error (read-only)
--FILE--
<?php
function f(lazy $x) {
    $x = 1;
}
?>
--EXPECTF--
Fatal error: Cannot modify lazy parameter $x in %s on line %d
