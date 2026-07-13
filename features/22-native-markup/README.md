# 22 — Native markup expressions (JSX-style syntax)

*Borrowed from JSX / Facebook's XHP — HTML as a first-class PHP value.*

A bare `<` in operand position begins a **markup expression**: JSX-style syntax that
evaluates to an in-memory tree of objects and renders to safely-escaped HTML. Markup
is a *value* — you can return it, pass it, store it, and compose it:

```php
class Greeting implements Html\Htmlable {
    public function __construct(public string $name) {}
    public function toHtml(): Html\Htmlable {
        return <>
            <h1 class="title">Hello, {$this->name}!</h1>
            <p>Welcome to PHP, where markup is a first-class expression.</p>
        </>;
    }
}

echo <Greeting name="Rasmus & <friends>" />;   // dynamic values escaped by default
```

It is **not** a new template dialect grafted onto PHP — it is pure compile-time
sugar. Every markup expression lowers, during compilation, to a plain `new`/call:

```php
$html = <button class="btn">Sign in</button>;
// compiles to exactly this — same AST, same opcodes:
$html = new \Html\Element('button', ['class' => 'btn'], ['Sign in']);
```

## Semantics

- **Elements** `<div class="x">…</div>` → `new Html\Element('div', [...], [...])`.
  Void elements (`<br>`, `<img>`) serialize HTML5-style with no closing tag.
- **Fragments** `<>…</>` group children with no wrapper → `new Html\Fragment([...])`.
- **Interpolation** `{$expr}` holds any expression; arrays flatten, `null` renders
  `""`, and anything implementing `Html\Htmlable` passes through un-escaped.
- **Escape-by-default.** Interpolations and attribute values are escaped with
  `htmlspecialchars(…, ENT_QUOTES | ENT_SUBSTITUTE | ENT_HTML401)`. The greppable
  opt-out for trusted strings is `Html\raw($s)`; `Html\escape($s)` is also provided.
- **Attributes** come in four forms: literal `class="x"`, `={expr}`, bare boolean
  (`required`), and spread `{...$attrs}`.
- **Components** — a capitalised/namespaced tag `<Card title="Hi" />` dispatches
  through `Html\render_component(Card::class, ['title' => 'Hi'])`; props map to named
  arguments, so signatures are type-checkable. A component body becomes its slot.
- **Dynamic tags** `<$tag>…</$tag>` classify the tag at runtime via
  `Html\render_dynamic(...)`.

Markup obeys ordinary expression semantics — attributes and children are argument
expressions, evaluated eagerly, scoped and typed like any other PHP code. JSX-style
whitespace normalization collapses indentation between block elements while
preserving meaningful inline spaces.

## How it works

Markup must begin at a **bare `<` in operand position**, and telling that apart from
the comparison and shift operators is a scanner-state change (`zend_language_scanner.l`)
that no extension could reach — which is why this lives in core rather than a PECL
extension. The scanner hands markup tokens to a small recursive parser
(`Zend/zend_markup.c`) that emits the equivalent `new`/call AST; from the parser
onwards nothing in the engine changes. The runtime objects (`Html\Element`,
`Html\Fragment`, `Html\Raw`, the `Html\Htmlable` interface, and the
`render_component` / `render_dynamic` / `raw` / `escape` helpers) ship as a new,
always-on, static `ext/html` extension (never shared, like `ext/random`). See
[`feature.patch`](feature.patch) and the [RFC](RFC.md) for the full design.

## Scope

This targets **HTML specifically**, not general XML: escaping uses
`htmlspecialchars`, void elements follow HTML5, no namespaces/CDATA/DTD. Emitting XML
(RSS, SVG, SOAP) remains the job of DOM / `XMLWriter`. Semantic validation of
attribute *values* is out of scope (a framework can enforce policy via a component);
attribute and tag *names* are always validated. See the RFC's Future Scope.

## Try it

```bash
scripts/setup.sh 22-native-markup && scripts/build.sh
features/22-native-markup/smoke.sh
```
