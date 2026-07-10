--TEST--
lazy: memoisation side-effects, exceptions, object mutation, function scope
--FILE--
<?php
// Each thunk in a chain runs exactly once, however many times read.
$log = new ArrayObject();
lazy $a = (function () use ($log) { $log[] = "A"; return 2; })();
lazy $b = (function () use ($log) { $log[] = "B"; return 3; })();
lazy $c = $a + $b;
echo $c, " ", $c, " ", $c, "\n";                 // 5 5 5
echo implode(",", $log->getArrayCopy()), "\n";   // A,B (once each)

// A lazy value that is an object can be mutated by handle.
lazy $obj = new stdClass();
$obj->tag = "once";
$obj->n = 5;
echo $obj->tag, " ", $obj->n, "\n";              // once 5

// An exception in the initializer propagates on read.
lazy $boom = (function () { throw new RuntimeException("boom"); })();
try {
    echo $boom;
} catch (Throwable $e) {
    echo "caught: ", $e->getMessage(), "\n";
}

// Lazy locals are per-call and memoised within a call.
function tagOf(): string {
    static $calls = 0;
    $calls++;
    lazy $tag = "call-" . $calls;    // captured at declaration, computed on read
    return $tag . "/" . $tag;        // forces once; same value twice
}
echo tagOf(), "\n";                              // call-1/call-1
echo tagOf(), "\n";                              // call-2/call-2

// A lazy holding an array composes with array functions (read).
lazy $xs = range(1, 5);
echo array_sum($xs), " ", count($xs), "\n";      // 15 5
?>
--EXPECT--
5 5 5
A,B
once 5
caught: boom
call-1/call-1
call-2/call-2
15 5
