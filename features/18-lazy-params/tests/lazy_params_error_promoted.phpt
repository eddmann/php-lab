--TEST--
promoted lazy constructor parameter is a compile error
--FILE--
<?php
class C {
    public function __construct(public lazy $x) {}
}
?>
--EXPECTF--
Fatal error: Lazy parameter $x cannot be promoted in %s on line %d
