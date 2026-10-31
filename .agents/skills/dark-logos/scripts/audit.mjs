// node audit.mjs <repo> <out-dir> [file.svg ...]
// Renders every logo like a dark bookmark card shows it (dark/<file> if present, else the original,
// 250px wide on the dark card color) and flags what looks wrong there:
//   hidden  - much of the logo has too little contrast against the card
//   box     - one flat color fills most of the logo area (white paper, plate, own badge)
//   glare   - large bright area
//   tall    - taller than the card's logo area (README format: wordmark, 250px wide)
//   tiny    - very small ink (thin text in a wide canvas)
// Writes <out-dir>/audit.json and <out-dir>/audit.html (light original | dark result, flagged first).
import fs from 'node:fs';
import path from 'node:path';
import { pathToFileURL } from 'node:url';
import puppeteer from 'puppeteer-core';

const [repo, out, ...only] = process.argv.slice(2);
const dir = path.join(repo, 'assets/brand-names');
const files = (only.length ? only.map((f) => path.basename(f)) : fs.readdirSync(dir).filter((f) => f.endsWith('.svg'))).sort();
const overrides = await import(pathToFileURL(path.join(repo, 'dark-logos.mjs')).href).then((m) => m.OVERRIDES ?? {});

// --card-rgb of dark cards in main.css, logo box of a card (background-size: min(250px, 80%) auto)
const CARD = [36, 36, 40];
const WIDTH = 250;
const MAX_HEIGHT = 160;

const browser = await puppeteer.launch({ executablePath: process.env.CHROME_PATH ?? 'C:/Program Files/Google/Chrome/Application/chrome.exe' });
const page = await browser.newPage();
await page.goto('about:blank');

const results = [];
for (const file of files) {
    const dark = path.join(dir, 'dark', file);
    const shown = fs.existsSync(dark) ? dark : path.join(dir, file);
    const svg = 'data:image/svg+xml;base64,' + fs.readFileSync(shown).toString('base64');

    const m = await page.evaluate(async (src, card, width) => {
        const img = new Image();
        img.src = src;
        await img.decode().catch(() => null);
        if (!img.naturalWidth) return null;
        const height = Math.round(width * img.naturalHeight / img.naturalWidth);
        const canvas = Object.assign(document.createElement('canvas'), { width, height });
        const ctx = canvas.getContext('2d');
        ctx.fillStyle = `rgb(${card})`;
        ctx.fillRect(0, 0, width, height);
        ctx.drawImage(img, 0, 0, width, height);
        const px = ctx.getImageData(0, 0, width, height).data;

        const lin = (c) => (c /= 255) <= .04045 ? c / 12.92 : ((c + .055) / 1.055) ** 2.4;
        const lum = (r, g, b) => .2126 * lin(r) + .7152 * lin(g) + .0722 * lin(b);
        const cardLum = lum(...card);
        let ink = 0, hidden = 0, bright = 0, x0 = width, y0 = height, x1 = 0, y1 = 0;
        const colors = new Map();
        for (let y = 0; y < height; y++) for (let x = 0; x < width; x++) {
            const i = (y * width + x) * 4;
            const [r, g, b] = [px[i], px[i + 1], px[i + 2]];
            if (Math.max(Math.abs(r - card[0]), Math.abs(g - card[1]), Math.abs(b - card[2])) <= 12) continue;
            ink++;
            x0 = Math.min(x0, x); y0 = Math.min(y0, y); x1 = Math.max(x1, x); y1 = Math.max(y1, y);
            const l = lum(r, g, b);
            if ((Math.max(l, cardLum) + .05) / (Math.min(l, cardLum) + .05) < 1.8) hidden++;
            if (l > .6) bright++;
            const key = (r >> 3) << 10 | (g >> 3) << 5 | b >> 3;
            colors.set(key, (colors.get(key) ?? 0) + 1);
        }
        const area = ink ? (x1 - x0 + 1) * (y1 - y0 + 1) : 0;
        return {
            height,
            ink,
            hidden: ink ? hidden / ink : 0,
            box: area ? Math.max(...colors.values()) / area : 0,
            glare: area ? bright / area : 0,
            inkHeight: ink ? y1 - y0 + 1 : 0,
            inkWidth: ink ? x1 - x0 + 1 : 0,
        };
    }, svg, CARD, WIDTH);

    const flags = [];
    if (!m) flags.push('broken');
    else {
        if (m.hidden > .35) flags.push('hidden');
        if (m.box > .6) flags.push('box');
        if (m.glare > .6) flags.push('glare');
        if (m.height > MAX_HEIGHT) flags.push('tall');
        if (m.inkHeight < 14 || m.ink < 600) flags.push('tiny');
    }
    results.push({ file, variant: fs.existsSync(dark), override: overrides[file] ?? null, flags, ...m });
}
await browser.close();

fs.mkdirSync(out, { recursive: true });
fs.writeFileSync(path.join(out, 'audit.json'), JSON.stringify(results, null, 1));

const flagged = results.filter((r) => r.flags.length);
const url = (f, sub = '') => pathToFileURL(path.join(dir, sub, f)).href;
const tile = (r) => `<div class="t${r.flags.length ? ' f' : ''}"><div style="background-color:#fff;background-image:url('${url(r.file)}')"></div><div style="background-color:rgb(${CARD});background-image:url('${r.variant ? url(r.file, 'dark') : url(r.file)}')"></div><p><span>${r.file.replace('.svg', '')}</span>${r.override ? `<em>${r.override}</em>` : ''}${r.flags.map((f) => `<b>${f}</b>`).join('')}</p></div>`;
fs.writeFileSync(path.join(out, 'audit.html'), `<!doctype html><meta charset=utf-8><title>Dark logo audit</title><style>
body{margin:0;padding:16px;background:#101012;color:#ddd;font:12px system-ui}h1{font-size:16px}
.g{display:grid;grid-template-columns:repeat(auto-fill,minmax(340px,1fr));gap:8px;margin-bottom:24px}
.t{display:grid;grid-template-columns:1fr 1fr;border:1px solid #333;border-radius:8px;overflow:hidden}.t.f{border-color:#a14a00}
.t>div{height:90px;background:center/min(150px,80%) auto no-repeat}
p{grid-column:1/3;margin:0;padding:3px 8px;background:#1c1c1e;display:flex;gap:6px;align-items:center}p span{margin-right:auto}
b{background:#3d1216;color:#ffb4ba;padding:0 5px;border-radius:5px;font-weight:600}em{background:#2a1f45;color:#d0bcff;padding:0 5px;border-radius:5px;font-style:normal}
</style><h1>${flagged.length} of ${results.length} logos flagged</h1><div class=g>${flagged.map(tile).join('')}</div>
<h1>All logos</h1><div class=g>${results.filter((r) => !r.flags.length).map(tile).join('')}</div>`);

console.log(`${results.length} logos, ${flagged.length} flagged -> ${path.join(out, 'audit.html')}`);
for (const r of flagged) console.log(`  ${r.file.padEnd(28)} ${r.flags.join(', ')}${r.override ? `  (override: ${r.override})` : ''}`);
