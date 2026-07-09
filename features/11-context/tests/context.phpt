--TEST--
context parameters / provide (implicits): resolution, propagation, scope
--FILE--
<?php

interface Clock { public function time(): int; }
class SystemClock implements Clock {
    public function __construct(private int $t) {}
    public function time(): int { return $this->t; }
}

function now(context Clock $c): int { return $c->time(); }

// 1. block form: value active for everything called inside, auto-supplied
provide new SystemClock(42) { echo now(), "\n"; }

// 2. deep propagation: a context param several calls down sees it
function a(): string { return b(); }
function b(): string { return c(); }
function c(context Clock $c): string { return "deep=" . $c->time(); }
provide new SystemClock(7) { echo a(), "\n"; }

// 3. statement form: active for the rest of the enclosing function
function handler(): void {
    provide new SystemClock(99);
    echo "stmt=" . now() . "\n";
}
handler();

// 4. nearest-wins: a nested provide shadows the outer, restored on exit
provide new SystemClock(1) {
    echo now(), "\n";                      // 1
    provide new SystemClock(2) { echo now(), "\n"; }   // 2
    echo now(), "\n";                      // 1 again
}

// 5. pop on exception: the value does not leak past the block
try {
    provide new SystemClock(5) { throw new Exception("boom"); }
} catch (Exception $e) {
    echo "caught\n";
}
try { now(); } catch (\Error $e) { echo $e->getMessage(), "\n"; }

// 6. default is used when nothing is provided
function withDefault(context Clock $c = new SystemClock(-1)): int { return $c->time(); }
echo "default=" . withDefault() . "\n";
provide new SystemClock(8) { echo "provided=" . withDefault() . "\n"; }

// 7. interface/inheritance match
class Toc extends SystemClock {}
provide new Toc(123) { echo now(), "\n"; }

// 8. context/provide remain usable as method names; `class Context` still parses
class Holder {
    public function context($x) { return "m$x"; }
    public function provide() { return "p"; }
}
echo (new Holder)->context(1), (new Holder)->provide(), "\n";
class Context { public int $x = 314; }
echo (new Context)->x, "\n";

?>
--EXPECT--
42
deep=7
stmt=99
1
2
1
caught
No context value of type Clock is available
default=-1
provided=8
123
m1p
314
