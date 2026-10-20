---
name: brand-logos
description: Add missing brand logos to assets/brand-names from the panel's "Missing Brand Logos" copy list. Use when the user pastes lines like "NomadList (nomadlist.com) -> nomadlist.svg (1×, 1 user)" or asks to generate, fetch or check missing logos. Checks every domain for redirects first, then grabs the logo with headless Chrome, transparent and in the README format.
---

# Brand logos

Input: the panel list (**Bookmarks › Missing Brand Logos › Copy list**), one line per logo:
`<title> (<domain>) -> <file>.svg (<count>×, <users> users)`. The file name is the suggestion of
`BrandLogos::suggest()` (`site/plugins/brands/index.php`) — use it as is.

## 1. Check every domain first

```bash
curl -sL -o /dev/null -w '%{http_code} %{url_effective}\n' -A 'Mozilla/5.0' https://<domain>
```

| Result | Action |
|---|---|
| Same site (also `www.`, a path, http → https) | generate the logo (step 2) |
| Redirect to **another brand** (renamed, acquired, shut down — e.g. iconduck.com → magnific.com) | **no logo, no alias, no extra code**. The brand is gone; report it with the target URL |
| Dead (DNS error, 4xx/5xx, parked domain) | no logo, report it |
| Site without an own logo (personal page, only an avatar, product shown with the maker's logo) | no logo, report it |

Never add matching rules (ALIASES, DOMAIN_ONLY, special cases) just to make a single bookmark match.

## 2. Generate

Scripts in `scripts/` (run them from a scratch directory, `puppeteer-core` is not a project dependency):

```bash
cp .claude/skills/brand-logos/scripts/*.mjs <scratch>/tool/ && cd <scratch>/tool
npm init -y && npm i puppeteer-core@23.11.1          # uses the installed Chrome (CHROME_PATH to override)
node grab.mjs <scratch>/out token=https://site ...    # token=url|css-selector when the automatic pick is wrong
node sheet.mjs <scratch>/out <scratch>/sheet.png       # contact sheet on the card background -> look at it
REPO=<repo> node build.mjs <scratch>/final token=<file>[@keep|@knockout] ...
node sheet.mjs <scratch>/final <scratch>/final.png     # look at it again
```

`grab.mjs` writes per token: `<token>.png` (element screenshot, 3×, transparent), `<token>.svg`
(standalone SVG file of the logo), `<token>.inline.svg`, `<token>.src.img` (original raster file)
and `<token>-page.png` (top of the page, to check the pick). Source preference:

1. Vector: the site's SVG file (`.svg`, also found in the page source with `curl … | grep -o '…logo…\.svg'`)
2. Original raster image (`.src.img`). Thumbnails (e.g. Wix `/v1/fill/w_122,…`) are blurry — request the original URL
3. Element screenshot (`.png`)

Rules (the user's preference):
- **No background at all** — cards are light (#fff / #ececf0). `build.mjs` darkens light, colorless parts
  of white logos and keeps colors.
- `@keep`: white sits on the logo's own color area (app icon tiles, a red circle) — don't darken
- `@knockout`: a plain box behind the logo (color of the top left pixel) is removed
- Prefer the full logo with the brand name (wordmark); icon only if the site has nothing else — mention it
- The page logo must be the brand of the bookmark, not the maker or a parent company

## 3. Finish

```bash
cp <scratch>/final/<token>.svg assets/brand-names/
npm run logos -- <token>.svg ...            # SVGO + WebP for embedded rasters
```

- Update the count in `assets/brand-names/README.md` ("Brand logos shown on bookmark cards (N SVGs)")
- Run the brand tests: `vendor/bin/pest tests/Feature/BrandLogosTest.php` (a test that uses a now
  existing logo as "missing" example needs a made-up brand instead)
- Check with the real domains that `BrandLogos::find($title, 'https://' . $domain)` now finds each logo
- Report: added logos, icon-only logos, and the skipped ones with their reason (redirect target, no logo, dead)
