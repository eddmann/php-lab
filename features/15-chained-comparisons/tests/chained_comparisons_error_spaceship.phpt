--TEST--
chained comparisons — the spaceship operator stays non-associative (parse error)
--FILE--
<?php
var_dump(1 <=> 2 <=> 3);
?>
--EXPECTF--
Parse error: syntax error, unexpected token "<=>" in %s on line %d
