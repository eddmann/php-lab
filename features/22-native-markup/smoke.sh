#!/usr/bin/env bash
# Smoke tests for native markup expressions (JSX-style syntax).
# Run after scripts/build.sh.
set -uo pipefail

REPO_ROOT="$(cd "$(dirname "${BASH_SOURCE[0]}")/../.." && pwd)"
PHP="$REPO_ROOT/php-src/sapi/cli/php"

pass=0; fail=0
check() { # name | expected | actual
  if [ "$2" = "$3" ]; then echo "ok   - $1"; pass=$((pass+1));
  else echo "FAIL - $1 (expected [$2], got [$3])"; fail=$((fail+1)); fi
}

check "element lowers to Html\\Element" "bool(true)" \
  "$("$PHP" -r 'var_dump((<div/>) instanceof Html\Element);')"

check "fragment lowers to Html\\Fragment" "bool(true)" \
  "$("$PHP" -r 'var_dump((<></>) instanceof Html\Fragment);')"

check "attributes + escaped interpolation" '<div>Hello Ada &amp; &lt;friends&gt; <em>world</em></div>' \
  "$("$PHP" -r '$name="Ada & <friends>"; echo <div>Hello {$name} <em>world</em></div>;')"

check "attribute values are escaped" '<a href="?a=1&amp;b=2">x</a>' \
  "$("$PHP" -r '$u="?a=1&b=2"; echo <a href={$u}>x</a>;')"

check "void element serializes with no closing tag" "<br>" \
  "$("$PHP" -r 'echo <br/>;')"

check "self-close and open/close are equivalent" "bool(true)" \
  "$("$PHP" -r 'var_dump((string)<div></div> === (string)<div/>);')"

check "arrays in interpolation flatten" '<ul><li>x</li><li>y</li></ul>' \
  "$("$PHP" -r '$i=["x","y"]; echo <ul>{array_map(fn($v)=><li>{$v}</li>, $i)}</ul>;')"

check "bare boolean attribute" '<input required>' \
  "$("$PHP" -r 'echo <input required/>;')"

check "attribute spread" '<input type="text" required>' \
  "$("$PHP" -r '$a=["type"=>"text","required"=>true]; echo <input {...$a} />;')"

check "Html\\raw() opts out of escaping" '<p><b>bold</b></p>' \
  "$("$PHP" -r 'echo <p>{Html\raw("<b>bold</b>")}</p>;')"

check "component tag dispatches to render_component" '<span class="badge">hi</span>' \
  "$("$PHP" -r 'class Badge implements Html\Htmlable { public function __construct(public string $label){}
                public function toHtml(): Html\Htmlable { return new Html\Element("span",["class"=>"badge"],[$this->label]); } }
                echo <Badge label="hi" />;')"

check "Htmlable child passes through un-escaped" '<div><span class="badge">hi</span></div>' \
  "$("$PHP" -r 'class Badge implements Html\Htmlable { public function __construct(public string $label){}
                public function toHtml(): Html\Htmlable { return new Html\Element("span",["class"=>"badge"],[$this->label]); } }
                echo <div><Badge label="hi" /></div>;')"

check "dynamic tag classifies at runtime" '<section>x</section>' \
  "$("$PHP" -r '$tag="section"; echo <$tag>x</$tag>;')"

check "bare < still parses as less-than" "bool(true)" \
  "$("$PHP" -r 'var_dump(1 < 2);')"

echo "----"
echo "passed: $pass, failed: $fail"
[ "$fail" -eq 0 ]
