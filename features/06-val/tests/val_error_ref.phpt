--TEST--
val — assign-by-reference is a compile error
--FILE--
<?php
val $x = 5;
$y =& $x;
?>
--EXPECTF--
Fatal error: Cannot reassign val $x in %s on line %d
