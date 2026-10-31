// Shrinks brand logos in assets/brand-names: embedded raster images are downscaled
// and re-encoded as WebP, then SVGO minifies the markup. A file is only rewritten when it gets smaller.
//
// Afterwards each logo gets its variant for dark cards (dark-logos.mjs).
//
// npm run logos                  -> all logos
// npm run logos -- github.svg    -> only these files
import fs from 'node:fs';
import path from 'node:path';
import sharp from 'sharp';
import { optimize } from 'svgo';
import { writeDarkVariant } from './dark-logos.mjs';

const dir = path.join(import.meta.dirname, 'assets/brand-names');

// logos are shown at most 250px wide, 3x covers HiDPI screens
const MAX_RASTER_WIDTH = 750;

const svgo = {
    multipass: true,
    plugins: ['preset-default', 'sortAttrs'],
};

const names = process.argv.slice(2).map((file) => path.basename(file));
const files = names.length ? names : fs.readdirSync(dir).filter((file) => file.endsWith('.svg')).sort();

/** Embedded PNG/JPEG -> downscaled WebP, if that is smaller */
async function shrinkRasters(svg) {
    const pattern = /data:image\/(?:png|jpe?g);base64,([A-Za-z0-9+/=\s]+)/g;
    let out = svg;

    for (const [uri, data] of svg.matchAll(pattern)) {
        const webp = await sharp(Buffer.from(data, 'base64'))
            .resize({ width: MAX_RASTER_WIDTH, withoutEnlargement: true })
            .webp({ quality: 90, alphaQuality: 100, effort: 6 })
            .toBuffer();
        const replacement = 'data:image/webp;base64,' + webp.toString('base64');

        if (replacement.length < uri.length) {
            out = out.replace(uri, replacement);
        }
    }

    return out;
}

let before = 0;
let after = 0;

for (const file of files) {
    const target = path.join(dir, file);
    const source = fs.readFileSync(target, 'utf8');

    // SVGO merges transform attributes and ignores CSS transforms, which shifts those shapes
    // (not text-transform)
    if (/style="(?:[^"]*;)?\s*transform:/.test(source)) {
        console.warn(`${file}: skipped, move the CSS transform in style="" to a transform attribute first`);
        before += source.length;
        after += source.length;
        await writeDarkVariant(file, source);
        continue;
    }

    const result = optimize(await shrinkRasters(source), { path: target, ...svgo }).data;

    before += source.length;

    if (result.length < source.length) {
        fs.writeFileSync(target, result);
        after += result.length;
        console.log(`${file}: ${source.length} -> ${result.length} bytes`);
    } else {
        after += source.length;
    }

    await writeDarkVariant(file, result.length < source.length ? result : source);
}

console.log(`${files.length} logos: ${(before / 1024).toFixed(0)} KB -> ${(after / 1024).toFixed(0)} KB`);
