# Bookmarks.cards

Bookmark.cards is a bookmarket collection tool in its simpliest form.

## Automated Brand Names Updates

This repository uses GitHub Actions workflows to automatically manage the brand-names submodule and regenerate brand CSS files:

### 1. Monthly Submodule Update Check
- **Workflow**: `.github/workflows/check-brand-names-update.yml`
- **Schedule**: Runs automatically on the 1st of each month
- **Manual Trigger**: Can be triggered manually via GitHub Actions UI
- **Actions**:
  - Checks for updates in the brand-names submodule
  - Creates a PR if a newer version is available
  - Includes labels: `dependencies`, `submodule-update`

### 2. Automatic CSS Generation
- **Workflow**: `.github/workflows/generate-brands-css.yml`
- **Trigger**: Runs when a PR updates the `assets/brand-names` directory
- **Actions**:
  - Generates `brands.css` using the npm script
  - Autoprefixes and minifies to `brands.min.css`
  - Commits changes back to the PR branch

## BrandsCSS

### Manual Generation

To manually generate and minify brand CSS files:

```bash
npm i --save-dev colorthief
npm i --save-dev jimp
npm i --save-dev get-image-colors
npm run generateBrandsCSS
npm run minifyBrandsCSS
```

The automated workflow handles these steps automatically when the brand-names submodule is updated.

## Brand Coverage Testing

View brand coverage statistics in the admin panel under **Site → Brands**.

The admin panel shows:
- Available brand logos count
- Brand coverage percentage
- List of bookmarks without matching brand logos

## Test

```bash
cd /your/project/path
npm install cypress --save-dev
npm init
npm run cy:open
npm run cy:test
```

## Support

In case you like Bookmark.cards or host it yourself consider supporting kreativ-anders by donating via [PayPal](https://paypal.me/kreativanders) or becoming a **GitHub Sponsor**.
