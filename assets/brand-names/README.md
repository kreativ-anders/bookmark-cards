# Brand Names

Brand logos shown on bookmark cards (397 SVGs). Formerly the standalone repo [kreativ-anders/brand-names](https://github.com/kreativ-anders/brand-names), now maintained here.

Besides brands that users bookmarked, the collection follows the most visited websites worldwide (e.g. [Similarweb](https://www.similarweb.com/blog/research/market-research/most-visited-websites/), [Traffic Lens](https://trafficlens.io/top-100-websites/)), so popular bookmarks get a logo out of the box — adult sites included.

## Adding a logo

1. Name the file after the brand: lowercase, letters only is safest, e.g. `github.svg`, `buymeacoffee.svg`
2. Follow the SVG format guideline below
3. Drop it into this folder — no build step. The `brands` plugin (`site/plugins/brands`) matches bookmark titles to file names (letters a-z only; the longest file name contained in the title wins)
4. Optimize it: `npm run logos -- <file>.svg` (or `npm run logos` for all logos)

The panel (**Site → Brands**) lists bookmark titles without a logo and the suggested file name.

## SVG format guideline

All `.svg` files follow this guideline (based on [Inkscape](https://inkscape.org/)):

1. Use the full logo with the brand name (wordmark). An icon or symbol alone is not enough.
2. Resize Page to brand/selection.
3. Transform size to 250px width.
4. Scale Page with the factor 1.2.
5. Align brand/selection to center.
6. Compress the file with `npm run logos` (`optimize-logos.mjs`): [SVGO](https://github.com/svg/svgo) minifies the markup, embedded PNG/JPEG images are downscaled to 750px width and re-encoded as WebP. Prefer vector logos — embedded raster images stay much larger.

## Trademarks

Logos only identify the website a user bookmarked. All brand names, logos and trademarks are the property of their respective owners; we are not affiliated with, endorsed by or sponsored by any of them.

Brand owners who want their logo updated or removed can contact **brand-names at kreativ-anders dot de**.
