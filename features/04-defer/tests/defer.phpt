--TEST--
defer — Go-style scope-exit cleanup (LIFO, on return and on exception)
--FILE--
<?php
// LIFO on normal return: deferred calls run after the body, last-scheduled first.
function lifo() {
    defer printf("a\n");
    defer printf("b\n");
    echo "body\n";
}
lifo();

// Runs during exception unwind too.
function boom() {
    defer printf("cleanup\n");
    throw new RuntimeException("boom");
}
try {
    boom();
} catch (Throwable $e) {
    echo "caught: ", $e->getMessage(), "\n";
}

// A defer inside a loop schedules one call per iteration; all run LIFO at exit.
function loop() {
    for ($i = 0; $i < 3; $i++) {
        defer printf("[%d]", $i);
    }
    echo "loop-body\n";
}
loop();
echo "\n";

// Go-faithful capture: the arguments are evaluated at the defer point, not at exit.
function capture() {
    $x = 1;
    defer printf("x=%d\n", $x);
    $x = 2;
}
capture();

// The receiver of a method call is likewise captured at the defer point.
class Logger {
    public function __construct(public string $tag) {}
    public function flush() { printf("flush %s\n", $this->tag); }
}
function receiver() {
    $log = new Logger("first");
    defer $log->flush();
    $log = new Logger("second");
}
receiver();

// Static calls work too.
class Greeter {
    public static function bye($name) { printf("bye %s\n", $name); }
}
function static_call() {
    defer Greeter::bye("x");
    echo "work\n";
}
static_call();

// Spread arguments are captured (flattened) at the defer point.
function spread() {
    $a = [1, 2, 3];
    defer printf("%d-%d-%d\n", ...$a);
    $a = [9];
    echo "spread-body\n";
}
spread();

// Deferred calls do not alter the return value.
function ret() {
    defer printf("after\n");
    return 42;
}
var_dump(ret());

// `defer` is only semi-reserved: still usable as a method name.
class HasMethod {
    public function defer() { return "method-ok\n"; }
}
echo (new HasMethod)->defer();
?>
--EXPECT--
body
b
a
cleanup
caught: boom
loop-body
[2][1][0]
x=1
flush first
work
bye x
spread-body
1-2-3
after
int(42)
method-ok
