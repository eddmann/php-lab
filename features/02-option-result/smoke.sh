#!/usr/bin/env bash
# Smoke tests for the `?` propagation operator + built-in Option/Result.
# Run after scripts/build.sh.
set -uo pipefail

REPO_ROOT="$(cd "$(dirname "${BASH_SOURCE[0]}")/../.." && pwd)"
PHP="$REPO_ROOT/php-src/sapi/cli/php"

pass=0; fail=0
check() { # name | expected | actual
  if [ "$2" = "$3" ]; then echo "ok   - $1"; pass=$((pass+1));
  else echo "FAIL - $1 (expected [$2], got [$3])"; fail=$((fail+1)); fi
}

check "None short-circuits" "1" \
  "$("$PHP" -r 'function f(): Option { $x = None()?; return Some($x); } var_dump(f() instanceof None);' | grep -c true)"

check "Some unwraps" "int(42)" \
  "$("$PHP" -r 'function f(): Option { $x = Some(41)?; return Some($x + 1); } var_dump(f()->value);')"

check "Ok unwraps" "int(20)" \
  "$("$PHP" -r 'function f(): Result { $x = Ok(10)?; return Ok($x * 2); } var_dump(f()->value);')"

check "Err propagates" "boom" \
  "$("$PHP" -r 'function f(): Result { $x = Err("boom")?; return Ok($x); } echo f()->getError();')"

check "first failure wins" "1" \
  "$("$PHP" -r 'function f(): Option { $a = Some(2)?; $b = None()?; return Some($a+$b); } var_dump(f() instanceof None);' | grep -c true)"

check "finally runs on short-circuit" "finally" \
  "$("$PHP" -r 'function f(): Option { try { $x = None()?; return Some($x); } finally { echo "finally"; } } f();')"

check "return type enforced (TypeError)" "caught" \
  "$("$PHP" -r 'function f(): int { $x = None()?; return $x; } try { f(); } catch (\TypeError $e) { echo "caught"; }')"

check "combinator: map on Some" "int(6)" \
  "$("$PHP" -r 'var_dump(Some(3)->map(fn($x) => $x * 2)->getValue());')"

check "combinator: unwrapOr on None" "def" \
  "$("$PHP" -r 'echo None()->unwrapOr("def");')"

# Existing ternary / coalesce / nullsafe must be unaffected.
check "ternary intact" "y" "$("$PHP" -r 'echo true ? "y" : "n";')"
check "short ternary intact" "x" "$("$PHP" -r 'echo "x" ?: "y";')"
check "coalesce intact" "d" "$("$PHP" -r 'echo null ?? "d";')"

# Documented caveat: `?` before + is read as a ternary (parenthesize instead).
check "caveat: parenthesized works" "int(2)" \
  "$("$PHP" -r 'function f(): mixed { return (Some(1)?) + 1; } var_dump(f());')"

echo "----"
echo "passed: $pass, failed: $fail"
[ "$fail" -eq 0 ]
