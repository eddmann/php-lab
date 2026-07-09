--TEST--
Option/Result — end-to-end: ?, ??, match, instanceof, combinators, factories
--FILE--
<?php
function parseAge(string $s): Result {
    return is_numeric($s) ? Ok((int)$s) : Err("not a number: $s");
}
function classify(string $s): Result {
    $age = parseAge($s)?;                 // ? short-circuits on Err
    return Ok($age >= 18 ? "adult" : "minor");
}
echo classify("20")->getValue(), "\n";
echo classify("x")->getError(), "\n";
echo (classify("15") ?? "??"), "\n";      // ?? unwraps Ok
echo (classify("x") ?? "fallback"), "\n"; // ?? falls back on Err
foreach (["9", "x", "40"] as $s)
    echo match (classify($s)) { Ok($v) => "ok:$v", Err($e) => "err:$e" }, "\n";
if (classify("30") instanceof Ok($v)) echo "bound $v\n";
echo Some(10)->map(fn($x)=>$x+1)->filter(fn($x)=>$x>5)->unwrapOr(0), "\n";
echo Some(3)->filter(fn($x)=>$x>5)->unwrapOr(-1), "\n";
echo Ok(5)->flatMap(fn($x)=>Ok($x*2))->mapErr(fn($e)=>"E:$e")->getValue(), "\n";
echo Result::try(fn()=>intdiv(10,2))->getValue(), "\n";
echo Result::try(fn()=>intdiv(10,0))->isErr() ? "caught" : "no", "\n";
echo Option::fromNullable(null)->isNone() ? "none" : "some", "\n";
var_dump(Option::all([Some(1), Some(2), Some(3)])->getValue());
echo Option::all([Some(1), None(), Some(3)])->isNone() ? "short" : "no", "\n";
echo Some(42), " ", None(), " ", Ok("hi"), " ", Err("bad"), "\n";
--EXPECT--
adult
not a number: x
minor
fallback
ok:minor
err:not a number: x
ok:adult
bound adult
11
-1
10
5
caught
none
array(3) {
  [0]=>
  int(1)
  [1]=>
  int(2)
  [2]=>
  int(3)
}
short
Some(42) None Ok("hi") Err("bad")
