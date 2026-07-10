--TEST--
apply on a non-object receiver is a TypeError (receiver must be bindable as $this)
--FILE--
<?php
try {
    apply (5) { $this->x = 1; };
} catch (\Throwable $e) {
    echo $e::class, ': ', $e->getMessage(), "\n";
}
?>
--EXPECT--
TypeError: Closure::call(): Argument #1 ($newThis) must be of type object, int given
