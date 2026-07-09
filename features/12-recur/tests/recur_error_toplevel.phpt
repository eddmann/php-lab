--TEST--
recur outside a function is a compile error
--FILE--
<?php
recur(1);
?>
--EXPECTF--
Fatal error: recur can only be used inside a function in %s on line %d
