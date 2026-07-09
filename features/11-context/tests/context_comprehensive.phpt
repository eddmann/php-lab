--TEST--
context/provide — resolution, nesting, statement form, default, missing, pop-on-throw
--FILE--
<?php
interface Clock { public function now(): int; }
class Fixed implements Clock { public function __construct(private int $t) {} public function now(): int { return $this->t; } }
class RequestId { public function __construct(public string $id) {} }

function currentTime(context Clock $c): int { return $c->now(); }
function deep(context Clock $c): string { return "t=" . $c->now(); }

// block form + resolution by interface
provide new Fixed(1000) {
    echo currentTime(), "\n";       // 1000, auto-supplied
    echo deep(), "\n";              // propagates down the call chain
}
// nearest wins + shadowing restore
provide new Fixed(1) {
    echo currentTime(), "\n";       // 1
    provide new Fixed(2) { echo currentTime(), "\n"; }  // 2
    echo currentTime(), "\n";       // 1 again
}
// statement form: active for rest of function
function handler(): void {
    provide new RequestId("abc");
    log_it();
}
function log_it(context RequestId $r): void { echo "req={$r->id}\n"; }
handler();
// default value when nothing provided
function withDefault(context RequestId $r = new RequestId("default")): string { return $r->id; }
echo withDefault(), "\n";
// missing value is an error
try { currentTime(); } catch (\Throwable $e) { echo "error: ", get_class($e), "\n"; }
// provide pops even on throw
try { provide new Fixed(9) { throw new Exception("boom"); } } catch (Throwable $e) {}
try { currentTime(); } catch (\Throwable $e) { echo "popped ok\n"; }
--EXPECT--
1000
t=1000
1
2
1
req=abc
default
error: Error
popped ok
