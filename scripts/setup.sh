#!/usr/bin/env bash
# Set up php-src with ONE feature's patch applied.
#
#   scripts/setup.sh <feature>
#
# Each feature is SELF-CONTAINED: its patch applies to a clean php-src @ the pinned
# tag and builds on its own — nothing is stacked. (Features 03-comprehensions and
# 11-context bundle their one real prerequisite: 03 needs the Option/Result types,
# 11 needs `defer`. Everything else is fully independent.)
#
# To try a different feature, just re-run with another name; the script resets
# php-src back to the pristine tag before applying.
set -euo pipefail

PHP_TAG="${PHP_TAG:-php-8.5.2}"
REPO_ROOT="$(cd "$(dirname "${BASH_SOURCE[0]}")/.." && pwd)"
SRC="$REPO_ROOT/php-src"

FEATURE="${1:-}"
if [ -z "$FEATURE" ] || [ ! -f "$REPO_ROOT/features/$FEATURE/feature.patch" ]; then
  echo "usage: scripts/setup.sh <feature>"
  echo "features:"; (cd "$REPO_ROOT/features" && ls -d */ | sed 's#/##; s/^/  /')
  exit 1
fi
PATCH="$REPO_ROOT/features/$FEATURE/feature.patch"

echo "==> Installing build dependencies"
sudo apt-get update -qq
sudo apt-get install -y build-essential autoconf libtool bison re2c pkg-config \
  libxml2-dev libsqlite3-dev

if [ ! -d "$SRC" ]; then
  echo "==> Cloning php-src @ $PHP_TAG"
  git clone --depth 1 --branch "$PHP_TAG" \
    https://github.com/php/php-src.git "$SRC"
fi

echo "==> Resetting php-src to a pristine $PHP_TAG tree"
git -C "$SRC" checkout -q .
git -C "$SRC" clean -qfdx

echo "==> Applying features/$FEATURE/feature.patch"
git -C "$SRC" apply "$PATCH"

echo "==> buildconf + configure (minimal CLI build)"
cd "$SRC"
./buildconf --force
./configure --disable-all --enable-cli --enable-tokenizer

echo "==> Done ($FEATURE). Now run: scripts/build.sh"
