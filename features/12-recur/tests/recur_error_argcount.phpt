--TEST--
recur with wrong arity is a compile error
--FILE--
<?php
function f(int $a, int $b) { return recur(1); }
?>
--EXPECTF--
Fatal error: recur expects exactly 2 arguments (one per declared parameter), 1 given in %s on line %d
