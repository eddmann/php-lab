--TEST--
val — re-declaration is a compile error
--FILE--
<?php
val $x = 5;
val $x = 2;
?>
--EXPECTF--
Fatal error: Cannot reassign val $x in %s on line %d
