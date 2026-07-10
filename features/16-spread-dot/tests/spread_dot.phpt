--TEST--
spread-dot: $coll*->m() maps a method (or property) over every element
--FILE--
<?php
class User {
    public function __construct(public string $name, public int $age) {}
    public function greet(): string { return "hi {$this->name}"; }
    public function agePlus(int $by): int { return $this->age + $by; }
}

$users = [new User("ada", 36), new User("bob", 40)];

// Method call across every element.
print_r($users*->greet());

// Method call with an argument.
print_r($users*->agePlus(10));

// Property read across every element.
print_r($users*->name);
print_r($users*->age);

// Keys are preserved.
$map = ["x" => new User("cy", 20), "y" => new User("di", 25)];
print_r($map*->name);

// Empty collection maps to an empty array.
var_dump([]*->greet());

// Multiplication / power / *= are unaffected by the new *-> token.
echo 6 * 7, " ", 2 ** 10, "\n";
$n = 4; $n *= 3; echo $n, "\n";
?>
--EXPECT--
Array
(
    [0] => hi ada
    [1] => hi bob
)
Array
(
    [0] => 46
    [1] => 50
)
Array
(
    [0] => ada
    [1] => bob
)
Array
(
    [0] => 36
    [1] => 40
)
Array
(
    [x] => cy
    [y] => di
)
array(0) {
}
42 1024
12
