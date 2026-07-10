--TEST--
lazy local — binding a reference to it is a compile error
--FILE--
<?php
lazy $x = 1;
$y =& $x;
?>
--EXPECTF--
Fatal error: Cannot modify lazy variable $x (lazy locals are read-only) in %s on line %d
