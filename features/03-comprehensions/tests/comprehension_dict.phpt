--TEST--
for {} yield $k => $v — dict comprehensions
--FILE--
<?php
// Basic dict comprehension over an array generator.
$users = [["id" => 10, "name" => "ann"], ["id" => 20, "name" => "bob"]];
var_export(for { $u = $users } yield $u["id"] => $u["name"]);
echo "\n";

// A guard filters before the pair is produced.
var_export(for { $x = [1, 2, 3, 4]; if $x % 2 === 0 } yield $x => $x * $x);
echo "\n";

// Computed keys; later keys win on collision.
var_export(for { $x = [1, 2, 3] } yield "k" => $x);
echo "\n";

// Two generators (cartesian) with a composed key.
var_export(for { $a = [1, 2]; $b = [10, 20] } yield "$a-$b" => $a + $b);
echo "\n";

// The plain list comprehension is unchanged.
print_r(for { $x = [1, 2, 3] } yield $x * 10);

?>
--EXPECT--
array (
  10 => 'ann',
  20 => 'bob',
)
array (
  2 => 4,
  4 => 16,
)
array (
  'k' => 3,
)
array (
  '1-10' => 11,
  '1-20' => 21,
  '2-10' => 12,
  '2-20' => 22,
)
Array
(
    [0] => 10
    [1] => 20
    [2] => 30
)
