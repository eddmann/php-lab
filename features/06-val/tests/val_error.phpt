--TEST--
val — reassigning a write-once local is a compile error
--FILE--
<?php
val $x = 5;
echo $x, "\n";
$x = 6;
?>
--EXPECTF--
Fatal error: Cannot reassign val $x in %s on line %d
