---
name: dark-logos
description: Check and fix how brand logos look on dark bookmark cards (assets/brand-names/dark, built by dark-logos.mjs). Use when the user names logos that look wrong in dark mode (invisible, white box, light plate, too big), after adding logos, or asks to find more problem logos. Renders every logo like the dark card, flags candidates, then fixes them with an override or in the original SVG.
---

# Dark logos

Dark cards show `assets/brand-names/dark/<file>` (when it exists) instead of the logo. `dark-logos.mjs`
builds these variants (`npm run logos:dark [-- file.svg ...]`, also part of `npm run logos`):

- black and dark greys become light greys, brand colors stay; colors too dark for the card get lighter in
  the same hue up to 3:1 (hex, `rgb()`, `hsl()`, `black`, missing fill, `fill="var(--…)"`)
- embedded raster images: white paper around the logo is cut out (antialiased edges unblended), then the
  same per pixel
- no change -> no dark file, the card shows the original

What the rules can't decide goes into `OVERRIDES` in `dark-logos.mjs` (one line per logo, sorted):

| Override | When | Examples |
|---|---|---|
| `original` | the logo already works on dark, recoloring breaks it (illustration with black outlines, dark shield with white text) | onepiece, epicgames, qq |
| `unbox` | the logo sits on its own box/badge or a white background rect: drop it. What sat on the box (white or dark parts, holes of a raster badge) takes the box color when that reads on the dark card, lighter parts stay a bit lighter. Without a plain rect the largest subpath of a compound path is the box (letters as holes become filled letters). Other shapes in a colored box's color only fake holes and go too | dev, nolt, ntfy, sichersatt, treegame, vystem, betterride, wikingerreisen, pornhub, namuwiki |
| `unbox-only` | drop the box, keep every other color (details that must stay dark) | sportspar (football) |
| `backdrop` | dark box whose letters are holes, the box should stay: original colors, white behind the box | mozilla |
| `cutout` | raster with white fills inside the shapes (outlined drawing, letter counters) | faircode, sparneuwagen |
| `mono` | raster that gets grainy or pale when recolored per pixel (textured, thin dark ink): one light color, opacity by darkness | scrivener, tinyhousemanufaktur |
| `plate` | last resort: original on a light plate (photos) | – |

Square icon logos (`tall`) also break the light card: widen the original's `viewBox` to 5:3
(`width="250" height="150" viewBox="-100 0 500 300"` for a 300×300 one), as done for dev, nolt, ntfy, sichersatt,
treegame, vystem and scihub.

## 1. Audit

`puppeteer-core` is not a project dependency, run the script from a scratch directory:

```bash
cp .agents/skills/dark-logos/scripts/audit.mjs <scratch>/tool/ && cd <scratch>/tool
npm init -y && npm i puppeteer-core@23.11.1                 # uses the installed Chrome (CHROME_PATH to override)
node audit.mjs <repo> <scratch>/audit [file.svg ...]       # all logos, or only these
```

It renders each logo like the dark card (250px wide on `#242428`), writes `audit.json` and `audit.html`
(light original | dark result, flagged first) and lists the flags:

| Flag | Meaning | Usual fix |
|---|---|---|
| `hidden` | much of the logo has < 1.8:1 against the card | a color form the rules miss -> extend `dark-logos.mjs`; else `original`/`unbox` |
| `box` | one flat color fills most of the logo area | white rect/paper -> `unbox`; letters as holes -> `backdrop`; a real colored badge (Spiegel, DEVK, Naver) is fine |
| `glare` | large bright area | usually a bold white wordmark (fine); a light box -> `unbox` |
| `tall` | higher than the card's logo area | square icon logos also break the light card: fix the **original** (wider `viewBox`, README format), e.g. scihub |
| `tiny` | very little ink | thin text in a wide canvas: crop the original |
| `broken` | does not render | check the SVG |

Flags are hints, not verdicts: screenshot `audit.html` (headless Chrome, `--allow-file-access-from-files`)
and look at every flagged tile before changing anything. Names the user gives count as flagged too —
map them to files first (`ls assets/brand-names | grep -i <part>`; spellings differ, e.g. "vsystem" is
`vystem.svg`, "Nima4K" is `nimak.svg`).

## 2. Fix

- Rule gaps (a color syntax, an attribute the default-fill check misses) -> fix `dark-logos.mjs`, so every
  logo benefits; re-run all (`npm run logos:dark`) and re-audit
- One logo -> add an override, `npm run logos:dark -- <file>.svg`
- Size problems -> edit the original SVG (`viewBox`/`width`/`height`), then `npm run logos -- <file>.svg`

## 3. Check and report

- Re-audit the touched logos and show the user a before/after sheet (light original | dark result) of
  every logo they named plus the new finds; let them decide on the borderline ones
- `npm run build` is not needed (logos are static files); run the brand tests:
  `php vendor/bin/pest tests/Feature/BrandLogosTest.php` (PHP >= 8.4)
- Commit only when the user asks
