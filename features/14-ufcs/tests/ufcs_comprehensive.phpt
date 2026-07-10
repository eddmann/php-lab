--TEST--
UFCS: chaining, reentrancy, spread, global resolution, inheritance, exceptions
--FILE--
<?php
namespace {
    final class Box { public function __construct(public int $v) {} }

    function inc(Box $b): Box { return new Box($b->v + 1); }
    function dbl(Box $b): Box { return new Box($b->v * 2); }

    // Chaining across free functions.
    echo (new Box(3))->inc()->dbl()->inc()->v, "\n";   // ((3+1)*2)+1 = 9

    // A free function whose body itself uses UFCS (trampoline reentrancy).
    function outer(Box $b): int { return $b->twice() + 1; }
    function twice(Box $b): int { return $b->v * 2; }
    echo (new Box(5))->outer(), "\n";                   // 11

    // Spread arguments follow the receiver.
    function total(Box $b, int ...$xs): int { return $b->v + array_sum($xs); }
    $args = [1, 2, 3];
    echo (new Box(10))->total(...$args), "\n";          // 16

    // Inherited methods still win over UFCS.
    function kind($o): string { return "free"; }
    class Base { public function kind(): string { return "base"; } }
    class Derived extends Base {}
    echo (new Derived)->kind(), "\n";                    // base

    // Exceptions from the free function propagate normally.
    function boom(Box $b): void { throw new \RuntimeException("bang"); }
    try {
        (new Box(0))->boom();
    } catch (\Throwable $e) {
        echo "caught: ", $e->getMessage(), "\n";
    }
}

namespace App {
    // A global-namespace function resolves even from a namespaced call site.
    echo (new \Box(7))->dbl()->v, "\n";                  // 14
}
?>
--EXPECT--
9
11
16
base
caught: bang
14
