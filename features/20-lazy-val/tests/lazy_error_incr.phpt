--TEST--
lazy local — increment is a compile error
--FILE--
<?php
lazy $x = 1;
$x++;
?>
--EXPECTF--
Fatal error: Cannot modify lazy variable $x (lazy locals are read-only) in %s on line %d
