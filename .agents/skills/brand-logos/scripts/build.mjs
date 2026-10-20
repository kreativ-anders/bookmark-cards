// node build.mjs <targetDir> token=source.(png|webp|img|svg) ...
// Wraps a logo in the brand-names format (README "SVG format guideline"):
// 250px wide, page scaled by 1.2, logo centered. No background: light grey/white
// parts are turned dark so the logo shows on the light cards, colors are kept.
import fs from 'node:fs';
import path from 'node:path';
import { createRequire } from 'node:module';

const require = createRequire(path.join(process.env.REPO ?? process.cwd(), 'package.json'));
const sharp = require('sharp');

const [target, ...jobs] = process.argv.slice(2);
const DARK = '#1d1d1f';

const num = (n) => +n.toFixed(3);

function wrap(inner, ratio) {
    const h = 250 * ratio;
    return { h, svg: (body) => `<svg xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink" width="250" height="${num(h)}" viewBox="0 0 300 ${num(h * 1.2)}">${body(25, num(h * 0.1), num(h))}</svg>` };
}

async function raster(file, mode) {
    let { data, info } = await sharp(file).ensureAlpha().raw().toBuffer({ resolveWithObject: true });

    // a plain box behind the logo (all four corners the same visible color) becomes transparent,
    // @knockout forces it with the color of the top left pixel, @keep never
    const at = (x, y) => [...data.slice((y * info.width + x) * 4, (y * info.width + x) * 4 + 4)];
    const corners = [at(0, 0), at(info.width - 1, 0), at(0, info.height - 1), at(info.width - 1, info.height - 1)];
    const box = corners.every((c) => c[3] > 20 && c.every((v, k) => Math.abs(v - corners[0][k]) < 12));
    if (mode === 'knockout' || (mode !== 'keep' && box)) {
        const [r0, g0, b0, a0] = data.slice(0, 4);
        for (let i = 0; i < data.length; i += 4) {
            if (Math.abs(data[i] - r0) + Math.abs(data[i + 1] - g0) + Math.abs(data[i + 2] - b0) + Math.abs(data[i + 3] - a0) < 60) data[i + 3] = 0;
        }
    }
    ({ data, info } = await sharp(data, { raw: info }).trim({ threshold: 1 }).raw().toBuffer({ resolveWithObject: true }));

    // share of light, colorless pixels among the visible ones
    let visible = 0, light = 0;
    for (let i = 0; i < data.length; i += 4) {
        if (data[i + 3] < 40) continue;
        visible++;
        const [r, g, b] = [data[i], data[i + 1], data[i + 2]];
        if (Math.max(r, g, b) - Math.min(r, g, b) < 40 && (r + g + b) / 3 > 170) light++;
    }
    const invert = mode !== 'keep' && light / visible > 0.25;
    if (invert) {
        for (let i = 0; i < data.length; i += 4) {
            const [r, g, b] = [data[i], data[i + 1], data[i + 2]];
            const avg = Math.round((r + g + b) / 3);
            // only light greys turn dark, dark parts stay as they are
            if (Math.max(r, g, b) - Math.min(r, g, b) < 40 && avg > 127) {
                data[i] = data[i + 1] = data[i + 2] = Math.max(29, 255 - avg);
            }
        }
    }

    const png = await sharp(data, { raw: info })
        .resize({ width: 750, withoutEnlargement: true })
        .png()
        .toBuffer();
    const meta = await sharp(png).metadata();
    const { h, svg } = wrap(null, meta.height / meta.width);

    return {
        invert,
        svg: svg((x, y, hh) => `<image x="${x}" y="${y}" width="250" height="${hh}" xlink:href="data:image/png;base64,${png.toString('base64')}"/>`),
    };
}

function vector(file, mode) {
    let src = fs.readFileSync(file, 'utf8')
        .replace(/<\?xml[^>]*>/g, '')
        .replace(/<!--[\s\S]*?-->/g, '')
        .replace(/<!DOCTYPE[^>]*>/gi, '')
        .trim();

    // white parts dark, the logo has no background of its own
    const invert = mode !== 'keep' && /(fill|stroke)(:|=")\s*(#fff(fff)?|white)\b/i.test(src);
    if (invert) src = src.replace(/((?:fill|stroke)(?::|="))\s*(#fff(?:fff)?|white)\b/gi, `$1${DARK}`);

    const open = src.match(/<svg\b[^>]*>/)[0];
    const attr = (n) => open.match(new RegExp(`\\s${n}="([^"]*)"`))?.[1];
    let [, , w, h] = (attr('viewBox') ?? `0 0 ${parseFloat(attr('width'))} ${parseFloat(attr('height'))}`).split(/[\s,]+/).map(Number);
    const viewBox = attr('viewBox') ?? `0 0 ${w} ${h}`;

    const { svg } = wrap(null, h / w);
    const cleaned = open
        .replace(/\s(width|height|x|y|viewBox|class|id|style|alt|aria-[\w-]+|enable-background|version|xml:space)="[^"]*"/g, '')
        .replace(/>$/, '');
    const inner = (x, y, hh) => `${cleaned} x="${x}" y="${y}" width="250" height="${hh}" viewBox="${viewBox}">` + src.slice(open.length);

    let out = svg(inner);
    // inline svgs using currentColor get the dark text color
    out = out.replace(/currentColor/g, DARK);
    return { invert, svg: out };
}

for (const job of jobs) {
    // token=file[@keep|@knockout]
    const [token, spec] = job.split('=');
    const [file, mode] = spec.split('@');
    const isSvg = fs.readFileSync(file, 'utf8').trimStart().match(/^(<\?xml|<svg|<!--)/);
    const { svg, invert } = isSvg ? vector(file, mode) : await raster(file, mode);
    fs.writeFileSync(path.join(target, `${token}.svg`), svg);
    console.log(`${token}.svg  ${isSvg ? 'vector' : 'raster'}${invert ? ', light parts darkened' : ''}  ${Math.round(svg.length / 1024)} KB`);
}
