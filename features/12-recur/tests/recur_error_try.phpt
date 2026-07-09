--TEST--
recur inside try/catch is a compile error
--FILE--
<?php
function f(int $n) { try { return recur($n); } catch (Exception $e) {} }
?>
--EXPECTF--
Fatal error: recur is not supported inside try/catch/finally in %s on line %d
