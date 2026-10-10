// Asset build (replaces Prepros): `npm run build` or `npm run watch`
// - CSS: assets/css/main.css -> assets/css/main.min.css, self-hosted fonts (node_modules/@fontsource*) -> assets/css/fonts/
// - JS:  assets/js/pico-modal.js + assets/js/main.js -> assets/js/main.min.js
//        offline.js -> offline.min.js (offline page)
//        Plain concatenation (no bundling), so top-level functions stay global for inline handlers.
import { build, transform } from 'esbuild';
import { readFile, writeFile } from 'node:fs/promises';
import { watch } from 'node:fs';

const css = { entry: 'assets/css/main.css', out: 'assets/css/main.min.css' };
const scripts = [
  { sources: ['assets/js/pico-modal.js', 'assets/js/main.js'], out: 'assets/js/main.min.js' },
  { sources: ['offline.js'], out: 'offline.min.js' },
];

async function buildCSS() {
  await build({
    entryPoints: [css.entry], outfile: css.out, bundle: true, minify: true, logLevel: 'warning',
    loader: { '.woff2': 'file' }, assetNames: 'fonts/[name]', // stable names, cached offline by UpUp (header.php)
  });
  console.log(`✔ ${css.out}`);
}

async function buildJS(js) {
  const source = (await Promise.all(js.sources.map((f) => readFile(f, 'utf8')))).join(';\n');
  const { code } = await transform(source, { loader: 'js', minify: true });
  await writeFile(js.out, code);
  console.log(`✔ ${js.out}`);
}

const run = (task) => task().catch((e) => console.error(e.message));

await Promise.all([run(buildCSS), ...scripts.map((js) => run(() => buildJS(js)))]);

if (process.argv.includes('--watch')) {
  watch(css.entry, () => run(buildCSS));
  scripts.forEach((js) => js.sources.forEach((f) => watch(f, () => run(() => buildJS(js)))));
  console.log('👀 watching for changes…');
}
