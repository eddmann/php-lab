--TEST--
apply: capture semantics, method context, control flow, exceptions
--FILE--
<?php
class Bag { public array $items = []; }

// Control flow inside the block; captured variable drives it.
$seed = ["a", "b", "c"];
$bag = apply (new Bag()) {
    foreach ($seed as $s) {
        $this->items[] = strtoupper($s);
    }
};
echo implode(",", $bag->items), "\n";                 // A,B,C

// Capture is by value at the apply point.
$n = 1;
$captured = apply (new Bag()) { $this->items[] = $n; };
$n = 999;
echo $captured->items[0], "\n";                        // 1

// Inside a method: the block's $this is the receiver, not the enclosing object.
class Factory {
    public string $prefix = "F-";
    public function make(string $s): Bag {
        $p = $this->prefix;                            // read enclosing $this first
        return apply (new Bag()) { $this->items[] = $p . $s; };
    }
}
echo (new Factory())->make("x")->items[0], "\n";       // F-x

// Local variables inside the block do not leak or affect the result.
$r = apply (new Bag()) {
    $tmp = 10;
    $this->items[] = $tmp * 2;
};
echo $r->items[0], "\n";                               // 20

// Exceptions thrown in the block propagate.
try {
    apply (new Bag()) { throw new RuntimeException("boom"); };
} catch (Throwable $e) {
    echo "caught: ", $e->getMessage(), "\n";
}

// The receiver expression is evaluated exactly once.
function makeBag(): Bag { echo "make "; return new Bag(); }
$once = apply (makeBag()) { $this->items[] = 1; };
echo count($once->items), "\n";                        // make 1
?>
--EXPECT--
A,B,C
1
F-x
20
caught: boom
make 1
