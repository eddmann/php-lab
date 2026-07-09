--TEST--
defer — LIFO, exceptions, capture, spread, static, loop, semi-reserved
--FILE--
<?php
// LIFO on normal return
function lifo() { defer printf("a\n"); defer printf("b\n"); defer printf("c\n"); print "body\n"; }
lifo();
// runs on exception unwind
function boom() { defer printf("cleanup\n"); throw new RuntimeException("x"); }
try { boom(); } catch (Throwable $e) { print "caught: {$e->getMessage()}\n"; }
// argument capture at defer point (Go-faithful)
function capture() { $x = 1; defer printf("captured %d\n", $x); $x = 2; }
capture();
// receiver capture
class Res { public function __construct(public string $id) {} public function close() { print "close {$this->id}\n"; } }
function res() { $r = new Res("A"); defer $r->close(); $r = new Res("B"); }
res();  // closes A
// spread args
function spread() { $args = [1,2,3]; defer printf("%d-%d-%d\n", ...$args); }
spread();
// static call
class S { public static function log(string $m) { print "log $m\n"; } }
function st() { defer S::log("done"); print "work\n"; }
st();
// defer in a loop: each iteration schedules, all run at function exit LIFO
function loop() { for ($i = 0; $i < 3; $i++) { defer printf("d%d\n", $i); } print "loopbody\n"; }
loop();
// semi-reserved name
class D { function defer() { return "ok"; } }
echo (new D)->defer(), "\n";
--EXPECT--
body
c
b
a
cleanup
caught: x
captured 1
close A
1-2-3
work
log done
loopbody
d2
d1
d0
ok
