--TEST--
val — unset() is a compile error
--FILE--
<?php
val $x = 5;
unset($x);
?>
--EXPECTF--
Fatal error: Cannot reassign val $x in %s on line %d
