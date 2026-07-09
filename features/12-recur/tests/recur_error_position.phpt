--TEST--
recur outside tail position is a compile error
--FILE--
<?php
function f(int $n) { $x = recur($n); }
?>
--EXPECTF--
Fatal error: recur is only supported in tail position: as a statement `recur(...);` or as `return recur(...);` in %s on line %d
