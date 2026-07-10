--TEST--
lazy local — reassignment is a compile error (read-only)
--FILE--
<?php
lazy $x = 1;
$x = 2;
?>
--EXPECTF--
Fatal error: Cannot modify lazy variable $x (lazy locals are read-only) in %s on line %d
