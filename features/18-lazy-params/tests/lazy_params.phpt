--TEST--
lazy params: by-name arguments — deferred, memoized, short-circuiting
--FILE--
<?php
// An unused lazy argument is never evaluated.
function boom(): string { echo "BOOM "; return "x"; }
function debug_log(bool $on, lazy $msg): void { if ($on) echo $msg, "\n"; }
debug_log(false, boom());
echo "quiet\n";
debug_log(true, boom());

// Forced exactly once, memoized across reads.
function once(): int { echo "eval "; return 42; }
function triple(lazy $x): int { return $x + $x + $x; }
echo triple(once()), "\n";

// The body starts before the arguments evaluate; forcing order is use order.
function t(string $tag): string { echo $tag; return $tag; }
function g(lazy $a, lazy $b): string { echo "-body-"; return $b . $a; }
echo ":", g(t("A"), t("B")), "\n";

// The expression is captured (by value) at the call site.
function id(lazy $x) { return $x; }
$n = 1;
$r = id($n + 10);
$n = 99;
echo $r, "\n";

// A throwing argument only throws when forced — and in the callee's frame.
function risky(): int { throw new RuntimeException("late"); }
function guard(bool $use, lazy $v): string {
    if (!$use) return "skipped";
    try { return "got:" . $v; } catch (Throwable $e) { return "caught:" . $e->getMessage(); }
}
echo guard(false, risky()), "\n";
echo guard(true, risky()), "\n";

// The assert idiom: diagnostics cost nothing on the happy path.
function assert_that(bool $cond, lazy $msg): string {
    if (!$cond) throw new AssertionError($msg);
    return "ok";
}
function expensive(): string { echo "EXPENSIVE "; return "diag"; }
echo assert_that(true, expensive()), "\n";
try { assert_that(false, expensive()); } catch (AssertionError $e) { echo "caught:", $e->getMessage(), "\n"; }

// User-defined control flow: the untaken branch never evaluates,
// so recursion through a lazy argument terminates.
function ifThenElse(bool $c, lazy $then, lazy $else) { return $c ? $then : $else; }
function fact(int $n): int { return ifThenElse($n <= 1, 1, $n * fact($n - 1)); }
echo fact(10), "\n";
?>
--EXPECT--
quiet
BOOM x
eval 126
:-body-BABA
11
skipped
caught:late
ok
EXPENSIVE caught:diag
3628800
