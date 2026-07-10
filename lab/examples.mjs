// One runnable example per feature — shared by the lab UI and wasm/verify.mjs.
export const EXAMPLES = {
  '01-unless': `<?php
unless (false) { echo "runs when false\\n"; }

$dry = false;
print "saved\\n" unless $dry;

unless (in_array(3, [1, 2])) { echo "no 3 here\\n"; } else { echo "found 3\\n"; }
`,
  '02-option-result': `<?php
function parseAge(string $s): Result {
    return is_numeric($s) ? Ok((int)$s) : Err("not a number: $s");
}
function classify(string $s): Result {
    $age = parseAge($s)?;              // ? short-circuits on Err
    return Ok($age >= 18 ? "adult" : "minor");
}

echo classify("20") ?? "?", "\\n";      // ?? unwraps Ok
echo classify("x") ?? "fallback", "\\n";

echo match (classify("15")) {
    Ok($v)  => "ok: $v",
    Err($e) => "err: $e",
}, "\\n";

echo Some(10)->map(fn($x) => $x + 1)->unwrapOr(0), "\\n";
`,
  '03-comprehensions': `<?php
print_r(for { $x = [1,2,3]; $y = [10,20] } yield $x + $y);
print_r(for { $x = range(1,9); if $x % 2 } yield $x * $x);

var_dump(for { $x = Some(1); $y = Some(2) } yield $x + $y);   // Some(3)

print_r(for { $x = [1,2,3,4]; if $x % 2 === 0 } yield $x => $x * $x);
`,
  '04-defer': `<?php
function copyish(): void {
    defer printf("close input\\n");
    defer printf("close output\\n");   // LIFO: runs first
    printf("copying...\\n");
}
copyish();

function boom(): void {
    defer printf("cleanup runs on throw too\\n");
    throw new RuntimeException("bang");
}
try { boom(); } catch (Throwable $e) { echo "caught: {$e->getMessage()}\\n"; }
`,
  '05-with': `<?php
class Person {
    public function __construct(public int $age, public string $status = "new") {}
}
$p = new Person(30);
$older = $p with { age: $p->age + 1, status: 'active' };
printf("orig: %d/%s\\n", $p->age, $p->status);
printf("new:  %d/%s\\n", $older->age, $older->status);

$overrides = ['status' => 'spread'];
$q = $p with { ...$overrides, age: 99 };
printf("q:    %d/%s\\n", $q->age, $q->status);
`,
  '06-val': `<?php
val $config = ['host' => 'localhost', 'port' => 8080];
echo $config['host'], "\\n";     // reads are fine

// Uncomment to see the compile error:
// $config = [];                 // Fatal: Cannot reassign val $config

val $x = 41;
echo $x + 1, "\\n";
`,
  '07-collections': `<?php
$a = Vector::of(1, 2, 3);
$b = $a->push(4);
echo "$a stays intact; $b is new\\n";

$m = Map::fromArray(['x' => 1]);
$m2 = $m->set('y', 2);
echo "m has y? ", $m->has('y') ? 'yes' : 'no', " — m2: ", $m2->get('y'), "\\n";

$s = Set::of(1, 2, 2, 3);
echo $s | Set::of(3, 4), "\\n";     // union
echo $a->map(fn($x) => $x * 10), "\\n";
`,
  '08-tagged-templates': `<?php
$id = 5;
$name = "O'Brien";
echo sql"SELECT * FROM users WHERE id = {$id} AND name = {$name}", "\\n";

function dbg(array $strings, array $values): string {
    return json_encode($strings) . " + " . json_encode($values);
}
echo dbg"a {$id} b {$name} c", "\\n";
`,
  '09-memoize': `<?php
$bodies = 0;
#[Memoize]
function fib(int $n): int {
    global $bodies; $bodies++;
    return $n < 2 ? $n : fib($n - 1) + fib($n - 2);
}
echo fib(30), "\\n";               // exponential by definition...
echo "body ran {$bodies}x\\n";      // ...but runs linearly
`,
  '10-refinements': `<?php
refinement Str for string {
    function shout(): string { return strtoupper($this) . '!'; }
}
refinement Arr for array {
    function sum(): int { return array_sum($this); }
}
using Str;
using Arr;

echo "hi"->shout(), "\\n";          // a method on a scalar
echo [1, 2, 3, 4]->sum(), "\\n";    // a method on an array
`,
  '11-context': `<?php
interface Clock { public function now(): int; }
class Fixed implements Clock {
    public function __construct(private int $t) {}
    public function now(): int { return $this->t; }
}

function currentTime(context Clock $c): int { return $c->now(); }

provide new Fixed(1000) {
    echo currentTime(), "\\n";       // auto-supplied, resolved by type
    provide new Fixed(2000) { echo currentTime(), "\\n"; }  // nearest wins
    echo currentTime(), "\\n";       // outer restored
}
`,
  '12-recur': `<?php
// Explicit recur: rebind params + jump — constant stack at any depth.
function sum_to(int $n, int $acc) {
    if ($n === 0) return $acc;
    return recur($n - 1, $acc + $n);
}
echo sum_to(1000000, 0), "\\n";       // plain recursion would die here

// Automatic self-TCO: no keyword needed for a plain tail self-call.
function fact_iter(int $n, int $acc) {
    if ($n <= 1) return $acc;
    return fact_iter($n - 1, $acc + $n);
}
echo fact_iter(1000000, 0), "\\n";

// Non-tail recursion is untouched (and still correct).
function fib(int $n): int { return $n < 2 ? $n : fib($n - 1) + fib($n - 2); }
echo fib(20), "\\n";
`,
  '13-placeholder': `<?php
// A bare \`_\` in a call argument becomes a closure — one param per \`_\`.
print_r(array_map(_ * 2, [1, 2, 3]));       // fn($p) => $p * 2

$xs = [3, 1, 2];
usort($xs, _ <=> _);                          // fn($a, $b) => $a <=> $b
print_r($xs);

echo array_reduce([1, 2, 3, 4], _ + _, 0), "\\n";

// Nested pipelines: each \`_\` binds to its own (innermost) call.
print_r(array_map(_ + 1, array_map(_ * 10, [1, 2, 3])));

// Captures surrounding variables by value (arrow-fn semantics).
$k = 100;
print_r(array_map(_ + $k, [1, 2]));
`,
  '14-ufcs': `<?php
final class Vec {
    public function __construct(public int $x, public int $y) {}
}
// Free functions — no methods on Vec.
function len(Vec $v): float       { return sqrt($v->x ** 2 + $v->y ** 2); }
function scale(Vec $v, int $k): Vec { return new Vec($v->x * $k, $v->y * $k); }
function add(Vec $a, Vec $b): Vec { return new Vec($a->x + $b->x, $a->y + $b->y); }

$v = new Vec(3, 4);
echo $v->len(), "\\n";                         // len($v) == 5

$r = $v->add(new Vec(1, 1))->scale(10);        // chains through free functions
echo "{$r->x},{$r->y}\\n";                     // 40,50

// A real method always wins; __call still takes precedence over UFCS.
class Named {
    public function __construct(public int $x, public int $y) {}
    public function len(): string { return "method"; }
}
echo (new Named(3, 4))->len(), "\\n";          // method
`,
  '15-chained-comparisons': `<?php
// a < b < c means a < b && b < c, each operand evaluated once.
var_dump(1 < 2 < 3);           // true
var_dump(3 < 2 < 1);           // false (short-circuits after 3 < 2)

// The range idiom, the way maths writes it.
$i = 5;
var_dump(0 <= $i < 10);        // true

// Operators may be mixed; the middle operand is evaluated only once.
function mid() { echo "mid()\\n"; return 5; }
var_dump(10 > mid() > 1);      // prints mid() once, then true

// The equality tier is unchanged: 1 == 1 == 1 is still a parse error.
`,
  '16-spread-dot': `<?php
class User {
    public function __construct(public string $name, public int $age) {}
    public function greet(): string { return "hi {$this->name}"; }
}
$users = [new User("ada", 36), new User("bob", 40)];

// Call a method on every element, collecting the results.
print_r($users*->greet());     // ["hi ada", "hi bob"]

// Read a property from every element (keys are preserved).
print_r($users*->name);        // ["ada", "bob"]

// Chains: each stage maps and yields an array.
class Box {
    public function __construct(public int $v) {}
    public function inc(): Box { return new Box($this->v + 1); }
    public function get(): int { return $this->v; }
}
$xs = [new Box(1), new Box(2), new Box(3)];
print_r($xs*->inc()*->get());  // [2, 3, 4]
`,
  '17-trailing-closures': `<?php
// A block after a call becomes its final closure argument.
$xs = [3, 1, 2];
usort($xs) { |$a, $b| return $a <=> $b; };
print_r($xs);

// Auto-captures surrounding variables (by value).
function run(callable $f) { return $f(); }
$greeting = "hello";
echo run() { return strtoupper($greeting); }, "\\n";

// Works on method and static calls too.
function each_of(array $xs, callable $f): void { foreach ($xs as $x) $f($x); }
each_of([1, 2, 3]) { |$x|
    $sq = $x * $x;
    echo "$x^2 = $sq\\n";
};
`,
  '18-lazy-params': `<?php
// A \`lazy\` parameter receives the UNEVALUATED argument expression:
// it runs only if read — and then just once (memoized).
function boom(): string { echo "BOOM! "; return "x"; }
function debug_log(bool $on, lazy $msg): void { if ($on) echo $msg, "\\n"; }

debug_log(false, boom());      // boom() never runs
echo "quiet so far\\n";
debug_log(true, boom());       // now it runs

// User-defined control flow: the untaken branch never evaluates,
// so recursion through an argument terminates.
function ifThenElse(bool $c, lazy $then, lazy $else) { return $c ? $then : $else; }
function fact(int $n): int { return ifThenElse($n <= 1, 1, $n * fact($n - 1)); }
echo fact(10), "\\n";           // eager evaluation would recurse forever
`,
  '19-infix': `<?php
// Any two-argument function can be written between its operands.
echo 3 max 7, "\\n";                       // max(3, 7)
echo 2 pow 10, "\\n";                      // pow(2, 10)
var_dump("hello" str_contains "ell");

// User functions too — left-associative, binds tighter than arithmetic.
function add(int $a, int $b): int { return $a + $b; }
echo 1 add 2 add 3, "\\n";                 // add(add(1,2),3)

function clamp(int $x, array $range): int {
    return max($range[0], min($range[1], $x));
}
echo 150 clamp [0, 100], "\\n";            // 100
`,
};
