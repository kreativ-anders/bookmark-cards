// Logo variants for dark bookmark cards: assets/brand-names/dark/<name>.svg
// - black and dark greys become light greys, brand colors stay
// - brand colors too dark for the dark card are mixed towards white (same hue) up to 3:1
// - embedded raster images: white paper around the logo is cut out first, then the same per pixel
// - OVERRIDES: decisions by eye, see .agents/skills/dark-logos
// Without any change there is no dark file and the card shows the original logo.
//
// npm run logos:dark                -> all logos (npm run logos also runs this per logo)
// npm run logos:dark -- github.svg  -> only these files
import fs from 'node:fs';
import path from 'node:path';
import sharp from 'sharp';

const dir = path.join(import.meta.dirname, 'assets/brand-names');
const darkDir = path.join(dir, 'dark');

// original: no variant, the logo works on the dark card as it is (illustrations with black outlines)
// unbox:    drop the logo's own box or badge; what sat on it takes its color when that reads on the dark card
// unbox-only: drop the box, keep every other color (details that must stay dark, e.g. a football)
// backdrop: a dark box whose letters are holes: original colors, white behind the box (looks like on a light card)
// cutout:   raster only, remove white inside the shapes too (white fills of an outlined drawing)
// mono:     raster only, one light color, opacity by darkness (textured or thin dark logos)
// plate:    original on a light plate (photos, recoloring breaks them)
export const OVERRIDES = {
    'betterride.svg': 'unbox',
    'dev.svg': 'unbox',
    'epicgames.svg': 'original',
    'faircode.svg': 'cutout',
    'mozilla.svg': 'backdrop',
    'namuwiki.svg': 'unbox',
    'nolt.svg': 'unbox',
    'ntfy.svg': 'unbox',
    'onepiece.svg': 'original',
    'pornhub.svg': 'unbox',
    'qq.svg': 'original',
    'scrivener.svg': 'mono',
    'sichersatt.svg': 'unbox',
    'sparneuwagen.svg': 'cutout',
    'sportspar.svg': 'unbox-only',
    'tinyhousemanufaktur.svg': 'mono',
    'treegame.svg': 'unbox',
    'vystem.svg': 'unbox',
    'wikingerreisen.svg': 'unbox',
};

// dark card surface (--card-rgb in main.css) and the plate color
const CARD = [36, 36, 40];
const PLATE = '#c4c4ca';
const LIGHT = '#f5f5f7';

const lin = (c) => (c /= 255) <= .04045 ? c / 12.92 : ((c + .055) / 1.055) ** 2.4;
const luminance = ([r, g, b]) => .2126 * lin(r) + .7152 * lin(g) + .0722 * lin(b);
export const contrast = (rgb) => (luminance(rgb) + .05) / (luminance(CARD) + .05);
const toHex = (rgb) => '#' + rgb.map((c) => Math.round(c).toString(16).padStart(2, '0')).join('');
const distance = (a, b) => Math.max(Math.abs(a[0] - b[0]), Math.abs(a[1] - b[1]), Math.abs(a[2] - b[2]));
const isWhite = (rgb) => Math.min(...rgb) >= 215 && Math.max(...rgb) - Math.min(...rgb) <= 40;

function parseHex(hex) {
    const h = hex.length === 4 ? [...hex.slice(1)].map((c) => c + c).join('') : hex.slice(1);
    return [0, 2, 4].map((i) => parseInt(h.slice(i, i + 2), 16));
}

/** Dark color -> its light counterpart for the dark card, null when it already works */
function recolorRgb(rgb) {
    const chroma = Math.max(...rgb) - Math.min(...rgb);

    // black/grey -> light grey, keeps the steps between greys
    if (chroma <= 40 && (rgb[0] + rgb[1] + rgb[2]) / 3 <= 110) {
        return rgb.map((c) => 245 - c * .4);
    }
    if (contrast(rgb) >= 3) {
        return null;
    }
    for (let t = .05; t < 1; t += .05) {
        const mixed = rgb.map((c) => c + (255 - c) * t);
        if (contrast(mixed) >= 3) {
            return mixed;
        }
    }
    return null;
}

const recolorHex = (hex) => {
    const rgb = recolorRgb(parseHex(hex));
    return rgb ? toHex(rgb) : null;
};

/* Raster images ------------------------------------------------------------ */

/** White paper behind the logo: opaque border that is mostly white (letters may touch it), else null */
function paperColor(px, w, h) {
    const border = [];
    for (let x = 0; x < w; x++) border.push(x, x + (h - 1) * w);
    for (let y = 1; y < h - 1; y++) border.push(y * w, y * w + w - 1);

    const rgb = (i) => [px[i * 4], px[i * 4 + 1], px[i * 4 + 2]];
    if (border.filter((i) => px[i * 4 + 3] > 200).length < border.length * .9) return null;

    // a colored border is the logo's own badge (Nolt), not paper
    const paper = border.filter((i) => px[i * 4 + 3] > 200 && isWhite(rgb(i)));
    if (paper.length < border.length * .5) return null;
    return [0, 1, 2].map((c) => paper.reduce((sum, i) => sum + px[i * 4 + c], 0) / paper.length);
}

/** Pixels reachable from the border through transparent pixels: everything outside the logo's shapes */
function outside(px, w, h) {
    const hit = new Uint8Array(w * h);
    const stack = [];
    for (let x = 0; x < w; x++) stack.push(x, x + (h - 1) * w);
    for (let y = 0; y < h; y++) stack.push(y * w, y * w + w - 1);
    while (stack.length) {
        const p = stack.pop();
        if (hit[p] || px[p * 4 + 3] >= 128) continue;
        hit[p] = 1;
        const x = p % w;
        if (x > 0) stack.push(p - 1);
        if (x < w - 1) stack.push(p + 1);
        if (p >= w) stack.push(p - w);
        if (p < w * (h - 1)) stack.push(p + w);
    }
    return hit;
}

/** Badge to cut-out: the badge turns transparent, its holes take the badge color (white parts: see tint) */
function unboxRaster(px, w, h, badge) {
    const out = outside(px, w, h);
    // antialiased outer edge of the badge: within 2px of the outside
    const nearOutside = (p) => {
        const x = p % w, y = (p - x) / w;
        for (let dy = -2; dy <= 2; dy++) for (let dx = -2; dx <= 2; dx++) {
            const nx = x + dx, ny = y + dy;
            if (nx >= 0 && nx < w && ny >= 0 && ny < h && out[ny * w + nx]) return true;
        }
        return false;
    };
    for (let p = 0; p < w * h; p++) {
        const i = p * 4;
        const isBadge = distance([px[i], px[i + 1], px[i + 2]], badge) <= 60;
        if (out[p] || (isBadge && (px[i + 3] === 255 || nearOutside(p)))) {
            if (isBadge) px[i + 3] = 0;
        } else if (isBadge || px[i + 3] < 255) {
            // inside the badge: hole -> badge color, antialiased edges by their alpha
            [px[i], px[i + 1], px[i + 2]] = badge.map(Math.round);
            px[i + 3] = 255 - px[i + 3];
        }
    }
}

/** Most frequent opaque color (the badge of a logo), null when no color covers a quarter of the logo */
function dominantColor(px) {
    const buckets = new Map();
    let opaque = 0;
    for (let i = 0; i < px.length; i += 4) {
        if (px[i + 3] < 200) continue;
        opaque++;
        const key = (px[i] >> 4) << 8 | (px[i + 1] >> 4) << 4 | px[i + 2] >> 4;
        const bucket = buckets.get(key) ?? { n: 0, sum: [0, 0, 0] };
        bucket.n++;
        [0, 1, 2].forEach((c) => bucket.sum[c] += px[i + c]);
        buckets.set(key, bucket);
    }
    const top = [...buckets.values()].sort((a, b) => b.n - a.n)[0];
    return top && top.n >= opaque * .25 ? top.sum.map((s) => s / top.n) : null;
}

/** GIMP-style color to alpha: the pixel looks the same over bg, without the bg share */
function unblend(px, i, bg) {
    let alpha = 0;
    for (let c = 0; c < 3; c++) {
        const v = px[i + c], b = bg[c];
        // slightly lighter than an off-white bg is still bg (the tiny 255 - b would make it opaque)
        alpha = Math.max(alpha, v > b + 16 ? (v - b) / (255 - b) : v < b ? (b - v) / (b || 1) : 0);
    }
    if (alpha < .004) {
        px[i + 3] = 0;
        return;
    }
    for (let c = 0; c < 3; c++) {
        px[i + c] = Math.min(255, Math.max(0, (px[i + c] - bg[c]) / alpha + bg[c]));
    }
    px[i + 3] = Math.round(px[i + 3] * alpha);
}

/** Cuts out bg: pixels close to it (from the border only, or everywhere) plus their antialiased edges */
function cutOut(px, w, h, bg, fromBorder) {
    const near = (p) => px[p * 4 + 3] > 0 && distance([px[p * 4], px[p * 4 + 1], px[p * 4 + 2]], bg) <= 100;
    const hit = new Uint8Array(w * h);
    const stack = [];

    if (fromBorder) {
        for (let x = 0; x < w; x++) stack.push(x, x + (h - 1) * w);
        for (let y = 0; y < h; y++) stack.push(y * w, y * w + w - 1);
        while (stack.length) {
            const p = stack.pop();
            if (hit[p] || !near(p)) continue;
            hit[p] = 1;
            const x = p % w;
            if (x > 0) stack.push(p - 1);
            if (x < w - 1) stack.push(p + 1);
            if (p >= w) stack.push(p - w);
            if (p < w * (h - 1)) stack.push(p + w);
        }
    } else {
        for (let p = 0; p < w * h; p++) hit[p] = near(p) ? 1 : 0;
    }

    // one pixel ring around the cut: antialiased edges keep no bg halo
    const cut = Uint8Array.from(hit);
    for (let p = 0; p < w * h; p++) {
        if (hit[p]) continue;
        const x = p % w;
        if ((x > 0 && hit[p - 1]) || (x < w - 1 && hit[p + 1]) || (p >= w && hit[p - w]) || (p < w * (h - 1) && hit[p + w])) cut[p] = 1;
    }
    for (let p = 0; p < w * h; p++) {
        if (cut[p]) unblend(px, p * 4, bg);
    }
}

/** Light part of an unboxed badge -> badge color, lighter parts stay a bit lighter (white = badge color) */
function shade(rgb, badge) {
    const base = Math.min(...badge);
    const whiteness = Math.min(1, Math.max(0, (Math.min(...rgb) - base) / (255 - base || 1)));
    return badge.map((c) => c + (255 - c) * (1 - whiteness) * .55);
}

/** One light color, opacity by darkness: dark ink -> opaque, light parts -> transparent (a stamp) */
function mono(px) {
    const lum = (i) => luminance([px[i], px[i + 1], px[i + 2]]);
    let darkest = 1;
    for (let i = 0; i < px.length; i += 4) if (px[i + 3] > 128) darkest = Math.min(darkest, lum(i));

    const light = parseHex(LIGHT);
    for (let i = 0; i < px.length; i += 4) {
        if (!px[i + 3]) continue;
        const ink = Math.min(1, Math.max(0, (1 - lum(i)) / (1 - darkest || 1)));
        [px[i], px[i + 1], px[i + 2]] = light;
        px[i + 3] = Math.round(px[i + 3] * ink);
    }
}

/** Dark variant of an embedded image (data URI), null when it needs no change */
async function darkRaster(uri, override) {
    const { data: px, info } = await sharp(Buffer.from(uri.slice(uri.indexOf(',') + 1), 'base64'))
        .ensureAlpha().raw().toBuffer({ resolveWithObject: true });
    const { width: w, height: h } = info;
    const before = Buffer.from(px);

    const paper = paperColor(px, w, h);
    if (paper) cutOut(px, w, h, paper, true);
    // white inside the shapes too (white fills of an outlined drawing)
    if (override === 'cutout') cutOut(px, w, h, paper ?? [255, 255, 255], false);

    if (override === 'mono') {
        mono(px);
    } else {
        let tint = null;
        if (override === 'unbox') {
            const badge = dominantColor(px);
            if (badge) {
                unboxRaster(px, w, h, badge);
                tint = contrast(badge) >= 3 ? badge : null;
            }
        }

        const cache = new Map();
        for (let i = 0; i < px.length; i += 4) {
            if (!px[i + 3]) continue;
            const rgb = [px[i], px[i + 1], px[i + 2]];
            const key = rgb.join();
            if (!cache.has(key)) cache.set(key, tint && Math.min(...rgb) >= 150 ? shade(rgb, tint) : recolorRgb(rgb));
            const out = cache.get(key);
            if (out) [0, 1, 2].forEach((c) => px[i + c] = Math.round(out[c]));
        }
    }

    if (px.equals(before)) return null;
    const webp = await sharp(px, { raw: { width: w, height: h, channels: 4 } })
        .webp({ quality: 90, alphaQuality: 100, effort: 6 }).toBuffer();
    return 'data:image/webp;base64,' + webp.toString('base64');
}

/* SVG ---------------------------------------------------------------------- */

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

/** Fill color of an element: hex, a gradient (mean of its stops) or the default black */
function fillColor(svg, element) {
    const hex = element.match(/fill[:=]"?\s*(#[0-9a-f]{3,6})\b/i)?.[1];
    if (hex) return parseHex(hex);

    let id = element.match(/fill[:=]"?\s*url\(#([^)]+)\)/i)?.[1];
    // a gradient may only link the stops of another one
    for (let hops = 0; id && hops < 3; hops++) {
        const gradient = svg.match(new RegExp(`<(linear|radial)Gradient\\b[^>]*\\sid="${id}"[^>]*?(?:/>|>[\\s\\S]*?</\\1Gradient>)`))?.[0] ?? '';
        const stops = [...gradient.matchAll(/stop-color[:=]"?\s*(#[0-9a-f]{3,6})\b/gi)].map((m) => parseHex(m[1]));
        if (stops.length) return [0, 1, 2].map((c) => stops.reduce((sum, s) => sum + s[c], 0) / stops.length);
        id = gradient.match(/href="#([^"]+)"/)?.[1];
    }
    return [0, 0, 0];
}

/**
 * Removes the logo's box: the first plain rectangle, else the largest subpath of a compound path
 * (letters as holes of the box then become filled letters). White and dark parts that sat on the box
 * take its color if that reads on the dark card, else they get recolored like any other logo.
 */
function unboxVector(svg) {
    let out = svg, color;
    const box = svg.match(/<rect\b[^>]*\/>|<path\b(?=[^>]*\sd="M\s*[-\d.]+[ ,][-\d.]+\s*h\s*[-\d.]+\s*v\s*[-\d.]+\s*(?:h\s*-[\d.]+|H\s*[-\d.]+)\s*z")[^>]*\/>/i)?.[0];

    if (box) {
        color = fillColor(svg, box);
        out = svg.replace(box, '');
    } else {
        let best = null;
        for (const [element, d] of svg.matchAll(/<path\b[^>]*\sd="([^"]*)"[^>]*>/gi)) {
            const subs = subpaths(d);
            for (const sub of subs) if (subs.length > 1 && (!best || sub.area > best.area)) best = { element, subs, ...sub };
        }
        if (!best) return svg;
        color = fillColor(svg, best.element);
        const d = best.subs.filter((s) => s.text !== best.text).map((s) => s.text).join('');
        out = svg.replace(best.element, best.element.replace(/\sd="[^"]*"/, ` d="${d}"`));
    }

    // other shapes in a colored box's color only fake holes in what sits on the box (ntfy): drop them as well
    // (not for white or black boxes: white text on another part of the logo is real, Pornhub)
    if (Math.max(...color) - Math.min(...color) > 40) {
        for (const [element] of out.matchAll(/<(?:path|rect|circle|ellipse|polygon)\b[^>]*\sfill="(?:#[0-9a-f]{3,6}|url\(#[^)]+\))"[^>]*\/>/gi)) {
            if (distance(fillColor(out, element), color) <= 40) out = out.replace(element, '');
        }
    }

    if (contrast(color) >= 3) {
        const onBox = (hex) => {
            const rgb = parseHex(hex);
            return isWhite(rgb) || (Math.max(...rgb) - Math.min(...rgb) <= 40 && (rgb[0] + rgb[1] + rgb[2]) / 3 <= 110);
        };
        out = out.replace(/((?:fill|stroke|stop-color|color)(?:="|:\s*))(#[0-9a-f]{6}\b|#[0-9a-f]{3}\b|white\b)/gi,
            (m, attr, value) => value.toLowerCase() === 'white' || onBox(value) ? attr + toHex(color) : m);
    }
    return out;
}

/** Subpaths of a path's d with an absolute start and their rough bounding box area (control points included) */
function subpaths(d) {
    const tokens = d.match(/[a-z]|-?(?:\d+\.?\d*|\.\d+)(?:e-?\d+)?/gi) ?? [];
    const arity = { m: 2, l: 2, h: 1, v: 1, c: 6, s: 4, q: 4, t: 2, a: 7, z: 0 };
    const result = [];
    let x = 0, y = 0, startX = 0, startY = 0, cmd = '', current = null, i = 0;

    while (i < tokens.length) {
        if (/[a-z]/i.test(tokens[i])) cmd = tokens[i++];
        const lower = cmd.toLowerCase(), rel = cmd === lower;
        if (lower === 'z') {
            [x, y] = [startX, startY];
            if (current) current.text += 'z';
            continue;
        }
        const args = tokens.slice(i, i + arity[lower]).map(Number);
        i += arity[lower];
        const points = [];
        if (lower === 'h') points.push([rel ? x + args[0] : args[0], y]);
        else if (lower === 'v') points.push([x, rel ? y + args[0] : args[0]]);
        else if (lower === 'a') points.push([rel ? x + args[5] : args[5], rel ? y + args[6] : args[6]]);
        else for (let k = 0; k < args.length; k += 2) points.push([rel ? x + args[k] : args[k], rel ? y + args[k + 1] : args[k + 1]]);

        if (lower === 'm') {
            current = { text: '', box: [Infinity, Infinity, -Infinity, -Infinity] };
            result.push(current);
            [startX, startY] = points[0];
            current.text = `M${startX} ${startY}`;
            cmd = rel ? 'l' : 'L';
        } else {
            current.text += `${cmd}${args.join(' ')}`;
        }
        for (const [px, py] of points) {
            current.box = [Math.min(current.box[0], px), Math.min(current.box[1], py), Math.max(current.box[2], px), Math.max(current.box[3], py)];
        }
        [x, y] = points[points.length - 1];
    }
    return result.map((s) => ({ text: s.text, area: (s.box[2] - s.box[0]) * (s.box[3] - s.box[1]) }));
}

/** White behind the largest subpath (the box whose letters are holes), same transform as its path */
function backdrop(svg) {
    let best = null;
    for (const [element, d] of svg.matchAll(/<path\b[^>]*\sd="([^"]*)"[^>]*>/gi)) {
        for (const sub of subpaths(d)) {
            if (!best || sub.area > best.area) best = { element, ...sub };
        }
    }
    if (!best) return null;

    const transform = best.element.match(/\stransform="[^"]*"/)?.[0] ?? '';
    return svg.replace(best.element, `<path fill="#fff"${transform} d="${best.text}"/>${best.element}`);
}

/** Shapes without a fill are black, unless a group, a style with fill or a stylesheet colors them (rough check) */
function hasDefaultFill(svg) {
    const visible = svg.replace(/<(defs|clipPath|mask)\b[\s\S]*?<\/\1>/g, '');
    // a class only colors something when the stylesheet sets a fill
    const colors = /<style\b[^>]*>[^<]*fill\s*:/.test(svg) ? '(?:fill="|class="|style="[^"]*fill:)' : '(?:fill="|style="[^"]*fill:)';
    return new RegExp(`<(path|rect|circle|ellipse|polygon|polyline|text|use)\\b(?![^>]*\\s${colors})`).test(visible)
        && !new RegExp(`<g\\b[^>]*\\s${colors}`).test(visible);
}

/** rgb()/rgba()/hsl()/hsla() -> rgb triple */
function parseFunctional(color) {
    const [name, ...args] = color.toLowerCase().match(/[a-z]+|[-\d.]+%?/g);
    const n = args.map(parseFloat);
    if (name.startsWith('rgb')) {
        return n.slice(0, 3).map((v, i) => args[i].endsWith('%') ? v * 2.55 : v);
    }
    const [h, s, l] = [n[0], n[1] / 100, n[2] / 100];
    const f = (k) => {
        const t = (k + h / 30) % 12;
        return 255 * (l - s * Math.min(l, 1 - l) * Math.max(-1, Math.min(t - 3, 9 - t, 1)));
    };
    return [f(0), f(8), f(4)];
}

/** Dark variant of an SVG, null when the original works on the dark card as it is */
export async function darkVariant(file, svg) {
    const override = OVERRIDES[file];
    if (override === 'original') return null;
    if (override === 'plate') return plate(svg);
    if (override === 'backdrop') return backdrop(svg);
    if (override === 'unbox-only') return unboxVector(svg);

    let out = override === 'unbox' ? unboxVector(svg) : svg;
    out = out.replace(/#[0-9a-f]{6}\b|#[0-9a-f]{3}\b/gi, (hex) => recolorHex(hex) ?? hex);
    out = out.replace(/\b(?:rgba?|hsla?)\([^)]*\)/gi, (color) => {
        const rgb = recolorRgb(parseFunctional(color));
        const alpha = color.match(/[-\d.]+%?/g)[3];
        if (!rgb) return color;
        return alpha === undefined ? toHex(rgb) : `rgba(${rgb.map(Math.round)},${alpha.endsWith('%') ? parseFloat(alpha) / 100 : alpha})`;
    });
    out = out.replace(/((?:fill|stroke|stop-color|color)(?:="|:\s*))black\b/gi, `$1${LIGHT}`);
    // CSS variables of the website the logo was taken from don't exist here: black
    out = out.replace(/((?:fill|stroke)=")var\([^"]*\)/gi, `$1${LIGHT}`);

    for (const [uri] of out.matchAll(/data:image\/(?:png|jpe?g|webp);base64,[A-Za-z0-9+/=\s]+/g)) {
        const dark = await darkRaster(uri, override);
        if (dark) out = out.replace(uri, dark);
    }

    if (out === svg && !hasDefaultFill(svg) && !/currentColor/i.test(svg)) {
        return null;
    }
    // default black fill and currentColor
    out = out.replace(/<svg\b(?![^>]*\sfill=)/, `<svg fill="${LIGHT}"`);
    return out.replace(/<svg\b(?![^>]*\scolor=)/, `<svg color="${LIGHT}"`);
}

/** Writes dark/<file>, or removes it when the original needs no variant */
export async function writeDarkVariant(file, svg) {
    const target = path.join(darkDir, file);
    const dark = await darkVariant(file, svg);

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

    let written = 0;
    for (const file of files) {
        if (await writeDarkVariant(file, fs.readFileSync(path.join(dir, file), 'utf8'))) written++;
    }
    console.log(`${files.length} logos: ${written} dark variants in assets/brand-names/dark`);
}
