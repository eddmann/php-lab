--TEST--
Collections — Vector/Map/Set full API, structural sharing, operators
--FILE--
<?php
$a = Vector::of(1, 2, 3);
$b = $a->push(4);
$c = $b->set(0, 99);
echo "$a | $b | $c\n";                  // $a unchanged (structural sharing)
echo $a->count(), " ", $b->count(), " ", $c->first(), " ", $c->last(), "\n";
echo $a->map(fn($x)=>$x*10)->reduce(fn($acc,$x)=>$acc+$x, 0), "\n";
echo $a->filter(fn($x)=>$x%2), "\n";
echo (Vector::of(1,2) + Vector::of(3,4)), "\n";
$m = Map::fromArray(['a'=>1, 'b'=>2]);
$m2 = $m->set('c', 3)->remove('a');
echo ($m->has('a')?"y":"n"), ($m2->has('a')?"y":"n"), " c=", $m2->get('c'), " z=", $m->get('z', -1), "\n";
echo Map::fromArray([])->set(1,'int')->set("1",'str')->count(), "\n";  // 1 vs "1" distinct
$s = Set::of(1, 2, 2, 3);
echo $s->count(), " has2=", ($s->has(2)?"y":"n"), "\n";
echo ($s | Set::of(3,4,5)), " / ", ($s & Set::of(2,3,9)), " / ", ($s - Set::of(2)), "\n";
$sum = 0; foreach (Vector::of(10,20,30) as $x) $sum += $x;
echo "iter=$sum count=", count(Vector::of(1,2,3,4,5)), "\n";
--EXPECT--
Vector(1, 2, 3) | Vector(1, 2, 3, 4) | Vector(99, 2, 3, 4)
3 4 99 4
60
Vector(1, 3)
Vector(1, 2, 3, 4)
yn c=3 z=-1
2
3 has2=y
Set(1, 2, 3, 4, 5) / Set(2, 3) / Set(1, 3)
iter=60 count=5
