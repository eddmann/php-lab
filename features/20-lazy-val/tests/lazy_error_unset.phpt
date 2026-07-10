--TEST--
lazy local — unset() is a compile error
--FILE--
<?php
lazy $x = 1;
unset($x);
?>
--EXPECTF--
Fatal error: Cannot modify lazy variable $x (lazy locals are read-only) in %s on line %d
