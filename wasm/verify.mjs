// Verify every wasm/dist/<feature> build by running its lab example under Node.
//   node wasm/verify.mjs [feature ...]
import { EXAMPLES } from '../lab/examples.mjs';
import { readdirSync, existsSync } from 'node:fs';
import { fileURLToPath } from 'node:url';
import { dirname, join } from 'node:path';

const here = dirname(fileURLToPath(import.meta.url));
const wanted = process.argv.slice(2);
const features = wanted.length ? wanted : readdirSync(join(here, 'dist')).sort();

let failures = 0;
for (const feature of features) {
  const mjs = join(here, 'dist', feature, 'php.mjs');
  if (!existsSync(mjs)) { console.log(`SKIP ${feature} (no build)`); continue; }
  const lines = [];
  try {
    const { default: createPHP } = await import(mjs);
    const php = await createPHP({
      print: (s) => lines.push(s),
      printErr: (s) => { if (s.trim() && !/munmap|dlopen/.test(s)) lines.push(`[err] ${s}`); },
    });
    php.FS.writeFile('/code.php', EXAMPLES[feature]);
    php.callMain(['/code.php']);
    const bad = lines.some((l) => /Fatal error|Parse error|\[err\]/.test(l));
    console.log(`${bad ? 'FAIL' : 'PASS'} ${feature} (${lines.length} lines)`);
    if (bad || wanted.length) lines.forEach((l) => console.log(`   | ${l}`));
    if (bad) failures++;
  } catch (e) {
    console.log(`FAIL ${feature}: ${e}`); failures++;
  }
}
process.exit(failures ? 1 : 0);
