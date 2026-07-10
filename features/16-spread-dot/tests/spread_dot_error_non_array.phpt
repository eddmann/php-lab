--TEST--
spread-dot over a non-array is a TypeError (from the array_map lowering)
--FILE--
<?php
class Box { public function get(): int { return 1; } }
$x = 5;
try {
    $x*->get();
} catch (\Throwable $e) {
    echo $e::class, ': ', $e->getMessage(), "\n";
}
?>
--EXPECT--
TypeError: array_map(): Argument #2 ($array) must be of type array, int given
