// Logo variants for dark bookmark cards: assets/brand-names/dark/<name>.svg
// - black and dark greys become light greys, brand colors stay
// - brand colors too dark for the dark card are mixed towards white (same hue) up to 3:1
// - logos with embedded raster images get a light rounded plate behind them
// Without any change (and for KEEP_ORIGINAL) there is no dark file and the card shows the original logo.
//
// npm run logos:dark                -> all logos (npm run logos also runs this per logo)
// npm run logos:dark -- github.svg  -> only these files
import fs from 'node:fs';
import path from 'node:path';

const dir = path.join(import.meta.dirname, 'assets/brand-names');
const darkDir = path.join(dir, 'dark');

// recoloring breaks these (illustrations with black outlines), the original works on the dark card
const KEEP_ORIGINAL = ['onepiece.svg'];

// dark card surface (--card-rgb in main.css) and the plate color
const CARD = [36, 36, 40];
const PLATE = '#c4c4ca';
const LIGHT = '#f5f5f7';

const lin = (c) => (c /= 255) <= .04045 ? c / 12.92 : ((c + .055) / 1.055) ** 2.4;
const luminance = ([r, g, b]) => .2126 * lin(r) + .7152 * lin(g) + .0722 * lin(b);
const contrast = (rgb) => (luminance(rgb) + .05) / (luminance(CARD) + .05);
const toHex = (rgb) => '#' + rgb.map((c) => Math.round(c).toString(16).padStart(2, '0')).join('');

function parseHex(hex) {
    const h = hex.length === 4 ? [...hex.slice(1)].map((c) => c + c).join('') : hex.slice(1);
    return [0, 2, 4].map((i) => parseInt(h.slice(i, i + 2), 16));
}

/** Dark color -> its light counterpart for the dark card, null when it already works */
function recolor(hex) {
    const rgb = parseHex(hex);
    const chroma = Math.max(...rgb) - Math.min(...rgb);

    // black/grey -> light grey, keeps the steps between greys
    if (chroma <= 40 && (rgb[0] + rgb[1] + rgb[2]) / 3 <= 110) {
        return toHex(rgb.map((c) => 245 - c * .4));
    }
    if (contrast(rgb) >= 3) {
        return null;
    }
    for (let t = .05; t < 1; t += .05) {
        const mixed = rgb.map((c) => c + (255 - c) * t);
        if (contrast(mixed) >= 3) {
            return toHex(mixed);
        }
    }
    return null;
}

/** Light plate behind the whole logo (viewBox, else width/height) */
function plate(svg) {
    const open = svg.match(/<svg\b[^>]*>/)?.[0] ?? '';
    const box = open.match(/viewBox="([^"]+)"/)?.[1].trim().split(/[\s,]+/).map(Number)
        ?? [0, 0, parseFloat(open.match(/\swidth="([\d.]+)/)?.[1]), parseFloat(open.match(/\sheight="([\d.]+)/)?.[1])];

    if (box.length !== 4 || box.some((n) => !Number.isFinite(n))) {
        return null;
    }
    const [x, y, w, h] = box;
    const radius = +(Math.min(w, h) * .12).toFixed(2);

    return svg.replace(open, `${open}<rect x="${x}" y="${y}" width="${w}" height="${h}" rx="${radius}" fill="${PLATE}"/>`);
}

/** Shapes without a fill attribute are black, unless a group, class or style colors them (rough check) */
function hasDefaultFill(svg) {
    const visible = svg.replace(/<(defs|clipPath|mask)\b[\s\S]*?<\/\1>/g, '');
    return /<(path|rect|circle|ellipse|polygon|polyline|text)\b(?![^>]*\s(fill|class|style)=)/.test(visible)
        && !/<g\b[^>]*\s(fill|class|style)=/.test(visible);
}

/** Dark variant of an SVG, null when the original works on the dark card as it is */
export function darkVariant(file, svg) {
    if (KEEP_ORIGINAL.includes(file)) {
        return null;
    }
    if (/<image\b|data:image\//.test(svg)) {
        return plate(svg);
    }

    let out = svg.replace(/#[0-9a-f]{6}\b|#[0-9a-f]{3}\b/gi, (hex) => recolor(hex) ?? hex);
    out = out.replace(/((?:fill|stroke|stop-color|color)(?:="|:\s*))black\b/gi, `$1${LIGHT}`);

    if (out === svg && !hasDefaultFill(svg) && !/currentColor/i.test(svg)) {
        return null;
    }
    // default black fill and currentColor
    out = out.replace(/<svg\b(?![^>]*\sfill=)/, `<svg fill="${LIGHT}"`);
    return out.replace(/<svg\b(?![^>]*\scolor=)/, `<svg color="${LIGHT}"`);
}
/** Writes dark/<file>, or removes it when the original needs no variant */
export function writeDarkVariant(file, svg) {
    const target = path.join(darkDir, file);
    const dark = darkVariant(file, svg);

    if (dark === null) {
        fs.rmSync(target, { force: true });
        return false;
    }
    fs.mkdirSync(darkDir, { recursive: true });
    fs.writeFileSync(target, dark);
    return true;
}

if (process.argv[1] && path.resolve(process.argv[1]) === import.meta.filename) {
    const names = process.argv.slice(2).map((file) => path.basename(file));
    const files = names.length ? names : fs.readdirSync(dir).filter((file) => file.endsWith('.svg')).sort();

    // drop variants of deleted logos
    if (!names.length && fs.existsSync(darkDir)) {
        fs.readdirSync(darkDir).filter((file) => !files.includes(file)).forEach((file) => fs.rmSync(path.join(darkDir, file)));
    }

    const written = files.filter((file) => writeDarkVariant(file, fs.readFileSync(path.join(dir, file), 'utf8')));
    console.log(`${files.length} logos: ${written.length} dark variants in assets/brand-names/dark`);
}
