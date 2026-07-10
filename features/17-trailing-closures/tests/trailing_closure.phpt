--TEST--
trailing closures: a block after a call becomes its final closure argument
--FILE--
<?php
// Zero-parameter block.
function twice(callable $f): void { $f(); $f(); }
twice() { echo "hi\n"; };

// Parameters via |...| pipes.
function each_of(array $xs, callable $f): void { foreach ($xs as $x) $f($x); }
each_of([1, 2, 3]) { |$x| echo $x * 10, "\n"; };

// Surrounding variables are captured automatically (by value).
function run(callable $f) { return $f(); }
$greeting = "hello";
echo run() { return strtoupper($greeting); }, "\n";

// Internal functions taking a trailing callback.
$xs = [3, 1, 2];
usort($xs) { |$a, $b| return $a <=> $b; };
echo implode(",", $xs), "\n";

// Method and static calls.
class Collection {
    public function __construct(private array $xs) {}
    public function each(callable $f): void { foreach ($this->xs as $x) $f($x); }
    public static function of(array $xs, callable $f): void { foreach ($xs as $x) $f($x); }
}
(new Collection([1, 2]))->each() { |$x| echo "e$x "; };
Collection::of([3, 4]) { |$x| echo "s$x "; };
echo "\n";

// The block-call is an expression: use its value directly.
function apply(array $xs, callable $f): array { return array_map($f, $xs); }
echo array_sum(apply([1, 2, 3]) { |$x| return $x * $x; }), "\n";

// Multiple statements in the body.
each_of([2]) { |$n|
    $sq = $n * $n;
    $cu = $sq * $n;
    echo "$sq,$cu\n";
};
?>
--EXPECT--
hi
hi
10
20
30
HELLO
1,2,3
e1 e2 s3 s4 
14
4,8
