--TEST--
UFCS does not mask the normal error when no method and no free function exist
--FILE--
<?php
class C {}
try {
    (new C)->nope();
} catch (\Throwable $e) {
    echo $e::class, ': ', $e->getMessage(), "\n";
}
?>
--EXPECT--
Error: Call to undefined method C::nope()
