# wasm — per-feature WebAssembly builds

Each feature's patched PHP compiled to WebAssembly with Emscripten — one build per
feature, mirroring the repo's one-patch-per-feature model. The lab
([`../lab/`](../lab/)) loads a feature's build in the browser and runs
code against it, spawning a fresh instance per run.

```
wasm/build.sh <feature>      # vanilla php-src -> apply feature.patch -> emconfigure/emmake
wasm/dist/<feature>/php.mjs  # Emscripten ES-module launcher (MODULARIZE)
wasm/dist/<feature>/php.wasm # the engine (~6 MB with -Oz)
wasm/verify.mjs              # run every build's example under Node
wasm_stubs.c                 # POSIX stubs absent under wasm (fibers/proc_open/...)
```

## Building

Requires `emcc` (Emscripten), `bison` ≥ 3, `re2c`, `git`:

```bash
wasm/build.sh 04-defer                 # one feature (~4 min)
for f in $(ls ../features); do wasm/build.sh "$f"; done   # all 12
node wasm/verify.mjs                   # smoke-test every build under Node
```

Build notes (the parts that differ from a native build):

- `--disable-opcache` — opcache is statically linked even under `--disable-all`
  and pulls in `initgroups`, absent on wasm.
- `--without-pcre-jit`, `--disable-fiber-asm` — no JIT / no asm fibers on wasm;
  the fibers ucontext fallback is stubbed out (`wasm_stubs.c`), so the `Fiber`
  class exists but cannot start.
- Linked with `-sMODULARIZE -sEXPORT_ES6` so each Run can instantiate a fresh
  engine (`createPHP()`), plus `-sALLOW_MEMORY_GROWTH`.
- The harmless `munmap() failed` warnings come from the Zend allocator's mmap
  emulation under Emscripten; the lab filters them.

## Lab

Serve the repo root (ES modules + wasm need HTTP, not file://):

```bash
python3 -m http.server 8090
open http://localhost:8090/lab/
```
