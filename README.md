# Bookmarks.cards

Bookmark.cards is a bookmarket collection tool in its simpliest form.

Built with [Kirby CMS](https://getkirby.com) 5, a small custom stylesheet (no CSS framework) and Stripe (via the local `memberkit` plugin).

## Setup

Requirements: PHP 8.3+ (8.2+ for production), Composer, Node.js

```bash
git clone https://github.com/kreativ-anders/bookmark-cards.git
cd bookmark-cards
composer install   # Kirby, Stripe (+ Pest for development)
npm ci             # esbuild, fonts (@fontsource), Cypress
```

Local configuration (Stripe keys, tiers) goes into `site/config/config.<host>.php`, e.g. `config.bookmark-cards.localhost.php` (git-ignored).

### Deployment

```bash
composer install --no-dev
```

Production settings go into the git-ignored `site/config/config.<domain>.php` on the server. Keep `debug` off there (default) and set long random secrets for `content.salt` (media and preview URLs) and `cookie.key` (cookie signatures):

```php
'content' => ['salt' => '…'],  // e.g. php -r 'echo bin2hex(random_bytes(32));'
'cookie'  => ['key'  => '…'],
```

Changing `cookie.key` invalidates existing login cookies once. The Panel runs without the Vue template compiler (`panel.vue.compiler => false`), so Panel plugins must ship precompiled.

`kirby/` and `vendor/` are not committed. Built assets (`assets/css/main.min.css`, `assets/js/main.min.js`, `offline.min.js`) are committed, so no Node.js is needed on the server.

## Assets

CSS and JS are built with esbuild (`build.mjs`):

```bash
npm run build   # one-off
npm run watch   # rebuild on change
```

- `assets/css/main.css` → `main.min.css` (custom design tokens, light + dark mode, WCAG 2.2 AA contrast); fonts Geist + Instrument Serif are self-hosted from npm → `assets/css/fonts/`
- The CSS targets existing IDs/classes used by `main.js`, `offline.js` and Cypress (e.g. `#bookmarks`, `#s_title`, `.card-title`, `span.tag`, `button.edit`, `#changeModal`) – keep them stable
- `assets/js/pico-modal.js` + `assets/js/main.js` → `main.min.js`
- `offline.js` → `offline.min.js`

## Brand Logos

Logos live in `assets/brand-names/*.svg` (see its README for the SVG guideline). The `brands` plugin (`site/plugins/brands`) matches them to bookmarks server-side — no CSS generation needed:

- Bookmark title and file names are normalized to lowercase `a-z`
- The **longest** file name contained in the title wins (`Buy me a coffee` → `buymeacoffee.svg`, not `coffee.svg`)
- `site()->brandLogo($title)` returns the logo URL, `/brands.json` serves all logos for the offline page

New SVGs in `assets/brand-names` are picked up automatically.

Brand coverage (logos available, bookmarks without logo, suggested file names) is shown in the panel under **Site → Brands**.

## Test

```bash
composer test     # Pest: Stripe integration (offline, fake Stripe client), brand logos, panel stats
npm run cy:test   # Cypress e2e against a running site (Stripe test mode)
```

Cypress uses `http://bookmark-cards.localhost` by default. Override it with `CYPRESS_BASE_URL`, e.g. for PHP's built-in server:

```bash
php -S bookmark-cards.localhost:8000 kirby/router.php
CYPRESS_BASE_URL=http://bookmark-cards.localhost:8000 npm run cy:test
```

In VS Code's integrated terminal run `env -u ELECTRON_RUN_AS_NODE npm run cy:test` (VS Code sets `ELECTRON_RUN_AS_NODE=1`, which prevents Cypress from starting). Kirby blocks an IP after 10 failed logins per hour — when running the suite often, raise `'auth' => ['trials' => 100]` in your local config.

## Support

In case you like Bookmark.cards or host it yourself consider supporting kreativ-anders by donating via [PayPal](https://paypal.me/kreativanders) or becoming a **GitHub Sponsor**.
