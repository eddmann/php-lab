--TEST--
Map — persistent immutable key/value map (HAMT, structural sharing)
--FILE--
<?php
$m = Map::fromArray(["a" => 1, "b" => 2]);
echo count($m), " ", $m->get("a"), $m->get("b"), "\n";
echo ($m->has("a") ? "y" : "n"), ($m->has("z") ? "y" : "n"), "\n";
echo $m->get("missing", "DEF"), "\n";

// Immutability: each op returns a new Map; the source is unchanged.
$a = Map::fromArray(["x" => 1]);
$b = $a->set("y", 2);
$c = $b->remove("x");
echo count($a), "/", count($b), "/", count($c), "\n";
echo ($a->has("y") ? "y" : "n"), ($c->has("x") ? "y" : "n"), "\n";

// int and string keys are distinct (unlike PHP arrays).
$k = (new Map())->set(1, "int")->set("1", "str");
echo count($k), " ", $k->get(1), " ", $k->get("1"), "\n";

// keys / values / map / filter (HAMT is unordered, so normalise for output).
$src = Map::fromArray(["a" => 1, "b" => 2, "c" => 3]);
$keys = $src->keys(); sort($keys);
$vals = $src->values(); sort($vals);
echo implode(",", $keys), " ", implode(",", $vals), "\n";
$doubled = $src->map(fn($v) => $v * 10)->toArray(); ksort($doubled);
echo json_encode($doubled), "\n";
$big = $src->filter(fn($v) => $v >= 2)->toArray(); ksort($big);
echo json_encode($big), "\n";

// Large-N integrity.
$n = new Map();
for ($i = 0; $i < 3000; $i++) $n = $n->set("k$i", $i);
$ok = count($n) === 3000;
for ($i = 0; $i < 3000; $i++) if ($n->get("k$i") !== $i) { $ok = false; break; }
echo $ok ? "big ok" : "big BAD", "\n";

// Non int/string key is a type error.
try { (new Map())->set([1], "x"); } catch (\TypeError $e) { echo "type error\n"; }
?>
--EXPECT--
2 12
yn
DEF
1/2/1
nn
2 int str
a,b,c 1,2,3
{"a":10,"b":20,"c":30}
{"b":2,"c":3}
big ok
type error
