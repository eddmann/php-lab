--TEST--
trailing closure combined with first-class callable syntax is a compile error
--FILE--
<?php
strlen(...) { return 1; };
?>
--EXPECTF--
Fatal error: Cannot combine first-class callable syntax with a trailing closure in %s on line %d
