--TEST--
val — compound assignment (+=) is a compile error
--FILE--
<?php
val $x = 5;
$x += 1;
?>
--EXPECTF--
Fatal error: Cannot reassign val $x in %s on line %d
