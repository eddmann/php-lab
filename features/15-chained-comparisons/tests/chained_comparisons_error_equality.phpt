--TEST--
chained comparisons — the equality tier stays non-associative (parse error)
--FILE--
<?php
var_dump(1 == 1 == 1);
?>
--EXPECTF--
Parse error: syntax error, unexpected token "==" in %s on line %d
