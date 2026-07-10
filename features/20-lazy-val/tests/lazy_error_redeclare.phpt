--TEST--
lazy local — re-declaration is a compile error
--FILE--
<?php
lazy $x = 1;
lazy $x = 2;
?>
--EXPECTF--
Fatal error: Cannot redeclare lazy variable $x in %s on line %d
