#!/usr/bin/env bash
# Build PHP from the (patched) source tree and regenerate the tokenizer data so
# that userland token_get_all()/token_name() know about any new tokens (T_UNLESS).
#
# Run scripts/setup.sh first. The resulting CLI binary is php-src/sapi/cli/php.
set -euo pipefail

REPO_ROOT="$(cd "$(dirname "${BASH_SOURCE[0]}")/.." && pwd)"
SRC="$REPO_ROOT/php-src"

cd "$SRC"

# First build: re2c regenerates the scanner, bison regenerates the parser, and a
# Makefile rule regenerates ext/tokenizer/tokenizer_data.c + tokenizer_data.stub.php
# from the grammar (so token_get_all()/token_name() see new tokens like T_UNLESS).
echo "==> make"
make -j"$(nproc)"

# The userland T_* *constants* are registered from tokenizer_data_arginfo.h, which
# is generated from the stub by gen_stub.php (not refreshed by plain make). Refresh
# it and rebuild so e.g. defined('T_UNLESS') is true.
echo "==> Regenerating tokenizer arginfo from stub"
"$SRC/sapi/cli/php" build/gen_stub.php ext/tokenizer/tokenizer_data.stub.php
make -j"$(nproc)"

echo "==> Built: $SRC/sapi/cli/php"
"$SRC/sapi/cli/php" -v
