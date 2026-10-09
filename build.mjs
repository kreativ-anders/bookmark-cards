// Asset build (replaces Prepros): `npm run build` or `npm run watch`
// - CSS: assets/css/main.css (+ @import of Pico from node_modules) -> assets/css/main.min.css
// - JS:  assets/js/pico-modal.js + assets/js/main.js -> assets/js/main.min.js
//        Plain concatenation (no bundling), so top-level functions stay global for inline handlers.
import { build, transform } from 'esbuild';
import { readFile, writeFile } from 'node:fs/promises';
import { watch } from 'node:fs';

const css = { entry: 'assets/css/main.css', out: 'assets/css/main.min.css' };
const js = { sources: ['assets/js/pico-modal.js', 'assets/js/main.js'], out: 'assets/js/main.min.js' };

async function buildCSS() {
  await build({ entryPoints: [css.entry], outfile: css.out, bundle: true, minify: true, logLevel: 'warning' });
  console.log(`✔ ${css.out}`);
}

async function buildJS() {
  const source = (await Promise.all(js.sources.map((f) => readFile(f, 'utf8')))).join(';\n');
  const { code } = await transform(source, { loader: 'js', minify: true });
  await writeFile(js.out, code);
  console.log(`✔ ${js.out}`);
}

const run = (task) => task().catch((e) => console.error(e.message));

await Promise.all([run(buildCSS), run(buildJS)]);

if (process.argv.includes('--watch')) {
  watch(css.entry, () => run(buildCSS));
  js.sources.forEach((f) => watch(f, () => run(buildJS)));
  console.log('👀 watching for changes…');
}
