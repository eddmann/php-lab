--TEST--
lazy local — using it as a foreach target is a compile error
--FILE--
<?php
lazy $x = 1;
foreach ([1, 2] as $x) {}
?>
--EXPECTF--
Fatal error: Cannot modify lazy variable $x (lazy locals are read-only) in %s on line %d
