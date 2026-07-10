--TEST--
spread-dot: chaining, namespaces, parenthesised operands, evaluation model
--FILE--
<?php
namespace App;

class Box {
    public function __construct(public int $v) {}
    public function inc(): Box { return new Box($this->v + 1); }
    public function get(): int { return $this->v; }
}

// Chaining: each stage maps and yields an array, which the next stage maps again.
$xs = [new Box(1), new Box(2), new Box(3)];
print_r($xs*->inc()*->inc()*->get());   // [3, 4, 5]

// Works from a namespaced call site (the desugar targets global array_map).
class Person {
    public function __construct(public string $name) {}
    public function shout(): string { return \strtoupper($this->name); }
}
print_r([new Person("a"), new Person("b")]*->shout());

// The collection operand may itself be an expression.
function makeBoxes(): array { return [new Box(10), new Box(20)]; }
print_r((makeBoxes())*->get());

// The method is invoked once per element, in order.
class Counter {
    public function __construct(public int $id) {}
    public function tick(): int { echo "tick{$this->id} "; return $this->id; }
}
$r = [new Counter(1), new Counter(2), new Counter(3)]*->tick();
echo "\n";
print_r($r);
?>
--EXPECT--
Array
(
    [0] => 3
    [1] => 4
    [2] => 5
)
Array
(
    [0] => A
    [1] => B
)
Array
(
    [0] => 10
    [1] => 20
)
tick1 tick2 tick3 
Array
(
    [0] => 1
    [1] => 2
    [2] => 3
)
