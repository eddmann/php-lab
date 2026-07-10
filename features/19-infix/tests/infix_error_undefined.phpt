--TEST--
infix call to an unknown function is an undefined-function error
--FILE--
<?php
try {
    echo 1 nope 2;
} catch (\Throwable $e) {
    echo $e::class, ': ', $e->getMessage(), "\n";
}
?>
--EXPECT--
Error: Call to undefined function nope()
