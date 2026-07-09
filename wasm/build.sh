#!/usr/bin/env bash
# Build ONE feature's patched PHP to WebAssembly.
#
#   wasm/build.sh <feature>            e.g. wasm/build.sh 04-defer
#
# Produces wasm/dist/<feature>/php.mjs + php.wasm — an Emscripten-modularized
# build usable from the browser (and Node, for verification):
#
#   import createPHP from './php.mjs';
#   const php = await createPHP({ print: s => out(s), printErr: s => err(s) });
#   php.FS.writeFile('/code.php', source);
#   php.callMain(['/code.php']);          // fresh instance per run
#
# Requirements: emscripten (emcc/emconfigure/emmake), bison >= 3, re2c, git.
# The php-src clone is cached in wasm/.php-src-pristine and copied per build.
set -euo pipefail

PHP_TAG="${PHP_TAG:-php-8.5.2}"
REPO_ROOT="$(cd "$(dirname "${BASH_SOURCE[0]}")/.." && pwd)"
WASM="$REPO_ROOT/wasm"
FEATURE="${1:-}"

if [ -z "$FEATURE" ] || [ ! -f "$REPO_ROOT/features/$FEATURE/feature.patch" ]; then
  echo "usage: wasm/build.sh <feature>"; (cd "$REPO_ROOT/features" && ls -d */ | sed 's#/##; s/^/  /'); exit 1
fi

# Homebrew keg-only bison/re2c if present
[ -d /opt/homebrew/opt/bison/bin ] && export PATH="/opt/homebrew/opt/bison/bin:$PATH"
[ -d /opt/homebrew/opt/re2c/bin ]  && export PATH="/opt/homebrew/opt/re2c/bin:$PATH"

PRISTINE="$WASM/.php-src-pristine"
if [ ! -d "$PRISTINE/Zend" ]; then
  echo "==> Cloning pristine php-src @ $PHP_TAG"
  GIT_CONFIG_GLOBAL=/dev/null git clone --depth 1 --branch "$PHP_TAG" \
    https://github.com/php/php-src.git "$PRISTINE"
fi

BUILD="$WASM/.build-$FEATURE"
echo "==> Preparing tree for $FEATURE"
rm -rf "$BUILD"; cp -a "$PRISTINE" "$BUILD"
git -C "$BUILD" apply --whitespace=nowarn "$REPO_ROOT/features/$FEATURE/feature.patch"

cd "$BUILD"
echo "==> buildconf + emconfigure"
./buildconf --force >/dev/null
emcc -Oz -c "$WASM/wasm_stubs.c" -o wasm_stubs.o
emconfigure ./configure \
  --disable-all --enable-cli --disable-cgi --disable-phpdbg --disable-opcache \
  --host=wasm32-unknown-emscripten \
  --without-pcre-jit --disable-fiber-asm \
  --without-valgrind --disable-shared --enable-static \
  CFLAGS="-Oz" >/dev/null

echo "==> emmake (this takes a few minutes)"
# Browser-friendly, modularized ES module; also runs under Node for verification.
LINK_FLAGS="-Oz \
  -sMODULARIZE=1 -sEXPORT_ES6=1 -sEXPORT_NAME=createPHP \
  -sENVIRONMENT=web,worker,node \
  -sEXPORTED_RUNTIME_METHODS=callMain,FS \
  -sINVOKE_RUN=0 -sEXIT_RUNTIME=1 \
  -sALLOW_MEMORY_GROWTH=1 -sSTACK_SIZE=8MB"
emmake make -j"$(getconf _NPROCESSORS_ONLN)" \
  EXTRA_LDFLAGS_PROGRAM="$BUILD/wasm_stubs.o $LINK_FLAGS" >/dev/null

DIST="$WASM/dist/$FEATURE"
mkdir -p "$DIST"
# emcc names the launcher after the make target; grab launcher + wasm
cp sapi/cli/php "$DIST/php.mjs"
cp sapi/cli/php.wasm "$DIST/php.wasm"
echo "==> Built $DIST"
ls -la "$DIST"
# the work tree is ~1.5GB; drop it on success (kept on failure for debugging)
cd "$WASM" && rm -rf "$BUILD"
