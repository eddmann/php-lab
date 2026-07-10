--TEST--
apply: run a block with $this bound to a receiver, returning the receiver
--FILE--
<?php
class Box {
    public int $x = 0;
    public int $y = 0;
    public array $tags = [];
}

// Binds $this to the receiver; the block mutates it; the expression IS the receiver.
$b = apply (new Box()) {
    $this->x = 3;
    $this->y = 4;
    $this->tags[] = "a";
    $this->tags[] = "b";
};
printf("%d,%d,%s\n", $b->x, $b->y, implode("|", $b->tags));

// Surrounding variables are captured by value (arrow-function semantics).
$host = "db.local";
$port = 5432;
class Cfg { public string $dsn = ""; }
$c = apply (new Cfg()) { $this->dsn = "$host:$port"; };
echo $c->dsn, "\n";

// Nested apply rebinds $this to each inner receiver.
class Node { public string $name = ""; public array $kids = []; }
$tree = apply (new Node()) {
    $this->name = "root";
    $this->kids[] = apply (new Node()) { $this->name = "child1"; };
    $this->kids[] = apply (new Node()) { $this->name = "child2"; };
};
echo $tree->name, ":", implode(",", array_map(fn($n) => $n->name, $tree->kids)), "\n";

// Parenthesised, the result can be dereferenced immediately.
class Counter { public int $n = 0; public function get(): int { return $this->n; } }
echo (apply (new Counter()) { $this->n = 42; })->get(), "\n";

// `apply` remains usable as a method name (semi-reserved).
class Svc { public function apply(string $s): string { return "svc:$s"; } }
echo (new Svc())->apply("go"), "\n";
?>
--EXPECT--
3,4,a|b
db.local:5432
root:child1,child2
42
svc:go
