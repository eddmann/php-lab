--TEST--
val — foreach write target is a compile error
--FILE--
<?php
val $x = 5;
foreach ([1] as $x) {}
?>
--EXPECTF--
Fatal error: Cannot reassign val $x in %s on line %d
