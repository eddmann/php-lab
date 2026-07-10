--TEST--
infix call to a function of the wrong arity is an ArgumentCountError
--FILE--
<?php
try {
    echo 3 strlen 4;
} catch (\Throwable $e) {
    echo $e::class, ': ', $e->getMessage(), "\n";
}
?>
--EXPECT--
ArgumentCountError: strlen() expects exactly 1 argument, 2 given
