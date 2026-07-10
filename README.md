# php-lab — PHP language experiments

A workbench for exploring **different language ideas in PHP** — by hacking them into
the real engine, not mocking them up. Each experiment pulls **PHP 8.5** source, adds
new syntax or types (from front-end desugars to compiler and engine work), and is
kept as a **self-contained patch** that applies to a clean `php-src` and builds and
tests on its own.

`php-src/` is git-ignored and cloned on demand; each feature's changes live in
[`features/`](features/).

## Features

Ideas borrowed from Rust, Scala, Go, Ruby, Kotlin, Clojure, Scheme, JS, C#, Python,
Raku, D, and Nim:

| # | Feature | From | Folder |
|---|---------|------|--------|
| 01 | `unless` — negated `if` | Ruby | [01-unless](features/01-unless/) |
| 02 | `Option`/`Result` + `?` propagation (`??`, `match`/`instanceof`, combinators) | Rust/Scala | [02-option-result](features/02-option-result/) |
| 03 | `for {} yield` comprehensions | Scala | [03-comprehensions](features/03-comprehensions/) |
| 04 | `defer` — scope-exit cleanup | Go | [04-defer](features/04-defer/) |
| 05 | `with` expressions — clone-with sugar | C#/Scala | [05-with](features/05-with/) |
| 06 | `val` — write-once locals | Kotlin/Rust | [06-val](features/06-val/) |
| 07 | Persistent `Vector`/`Map`/`Set` | Clojure | [07-collections](features/07-collections/) |
| 08 | Tagged template strings | JavaScript | [08-tagged-templates](features/08-tagged-templates/) |
| 09 | `#[Memoize]` executable decorator | Python | [09-memoize](features/09-memoize/) |
| 10 | Refinements — scoped extension methods | Ruby | [10-refinements](features/10-refinements/) |
| 11 | Context parameters — `context`/`provide` | Scala | [11-context](features/11-context/) |
| 12 | `recur` + automatic self-TCO — constant-stack tail recursion | Clojure/Scheme | [12-recur](features/12-recur/) |
| 13 | Placeholder lambdas — `_` implicit params | Scala/Raku | [13-placeholder](features/13-placeholder/) |
| 14 | Uniform Function Call Syntax — `$obj->freeFn()` | D/Nim | [14-ufcs](features/14-ufcs/) |

Each folder holds `feature.patch`, a `README.md` writeup, an `RFC.md` proposal, a
`tests/` suite, and `smoke.sh`.

## Each patch stands alone

The patches are **not** a stacked series — you apply exactly one to a pristine
`php-src` and it builds. Every feature has been compiled from clean PHP 8.5 and its
tests run to green in isolation. Only two carry their one real prerequisite: `03`
bundles the `Option`/`Result` types it iterates, `11` bundles `defer`.

## Usage

```bash
scripts/setup.sh 04-defer      # clone php-src@TAG, reset clean, apply this feature, configure
scripts/build.sh               # build -> php-src/sapi/cli/php
features/04-defer/smoke.sh      # smoke test
```

Re-run `scripts/setup.sh <feature>` for another feature; it resets `php-src` first,
so features never interact.

## Try it in the browser

Each feature is also compiled to **WebAssembly** (one build per feature) and
playable in the browser lab — pick a feature, edit the example, hit Run:

```bash
wasm/build.sh 04-defer         # build a feature to wasm (see wasm/README.md)
python3 -m http.server 8090    # serve the repo root
open http://localhost:8090/lab/
```

## License

[MIT](LICENSE) for the original work in this repo (patches, lab, scripts, docs).
The prebuilt `wasm/dist/` artifacts embed the PHP interpreter (PHP License v3.01)
and Emscripten runtime glue; the `features/` patches are diffs against `php-src`
(PHP License v3.01). See [LICENSE](LICENSE) for details.
