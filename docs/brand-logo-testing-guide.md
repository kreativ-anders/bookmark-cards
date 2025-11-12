# Brand Logo Testing - User Guide

This guide explains how to test and monitor which bookmarks have matching brand logos in the bookmark.cards application.

## Overview

Bookmark.cards displays brand logos alongside bookmarks to improve visual recognition. However, not all bookmarks may have matching brand logos available. This testing functionality helps administrators:

- Monitor overall brand coverage across all bookmarks
- Identify which bookmarks lack brand logos
- Prioritize which brand logos to create next
- Track improvements over time

## Two Ways to Check Brand Coverage

### Option 1: Admin Panel (Recommended)

The easiest way to check brand coverage is through the Kirby admin panel.

#### Steps:

1. **Log in to the Admin Panel**
   - Navigate to your bookmark.cards site
   - Click "Panel" or go to `/panel`
   - Log in with admin credentials

2. **View Brand Statistics**
   - Click on "Site" in the main navigation
   - Scroll to the "Brands" section
   
3. **Interpret the Statistics**
   - **Available Brands**: Number of brand logos currently in the system
   - **Brand Coverage**: Percentage of bookmarks that have matching logos
   - **Bookmarks Without Brands**: Count of bookmarks that need logos

#### Example:
```
Brands
- Available Brands: 139
- Brand Coverage: 78.5%
- Bookmarks Without Brands: 265
```

This tells you that:
- You have 139 brand logos available
- 78.5% of your bookmarks have matching logos
- 265 bookmarks still need brand logos

### Option 2: CLI Script (For Detailed Analysis)

For a more detailed analysis, use the command-line script.

#### Requirements:
- Node.js installed
- Terminal/SSH access to the server
- Access to the bookmark.cards directory

#### Steps:

1. **Navigate to Project Directory**
   ```bash
   cd /path/to/bookmark-cards
   ```

2. **Run the Script**
   ```bash
   npm run check-brands
   ```
   or
   ```bash
   node scripts/check-brand-coverage.js
   ```

3. **Review the Report**
   
   The script will output:
   - Summary statistics
   - List of bookmarks without brands
   - Suggested brand names for each missing logo
   - Recommendations for next steps

#### Example Output:
```
============================================================
Brand Coverage Analysis Report
============================================================

📊 Available brands in brands.css: 139
📚 Total bookmarks found: 1234

============================================================
Summary
============================================================
✅ Bookmarks with brands: 969
❌ Bookmarks without brands: 265
📈 Coverage: 78.5%

============================================================
Bookmarks Without Matching Brands
============================================================
1. "Notion" (used 45x)
   Suggested brand names: "notion" or "notion"
2. "Figma" (used 38x)
   Suggested brand names: "figma" or "figma"
3. "Linear" (used 22x)
   Suggested brand names: "linear" or "linear"
...

============================================================
💡 Recommendation
============================================================
Consider creating brand logos for the most commonly used bookmarks.
Add SVG files to assets/brand-names/ and run:
  npm run generateBrandsCSS
```

## Understanding Brand Matching

### How It Works

The system matches bookmarks to brands based on the bookmark's title:

1. **Title Normalization**: The bookmark title is converted to lowercase
2. **Exact Match Check**: Checks if a brand exists with the exact title
3. **No-Space Match Check**: Checks if a brand exists with spaces removed

### Examples

| Bookmark Title | Matches Brand | Reason |
|---------------|---------------|--------|
| "Google" | `google` | Exact match (lowercase) |
| "Google Drive" | `googledrive` | Spaces removed |
| "GitHub" | `github` | Exact match (lowercase) |
| "Slack" | `slack` | Exact match (lowercase) |

### Edge Cases

- **Case Sensitivity**: "GitHub", "github", and "GITHUB" all match the same brand
- **Spaces**: "Google Drive", "GoogleDrive" both work if a brand named "googledrive" exists
- **Special Characters**: Not currently normalized, so "Dropbox (Beta)" won't match "dropbox"

## Improving Brand Coverage

### Step 1: Identify Missing Brands

Use either the admin panel or CLI script to identify which brands are missing.

### Step 2: Create Brand Logos

1. Create SVG files for the missing brands
2. Name them according to the suggested brand name (lowercase, no spaces)
3. Example: For "Google Drive", create `googledrive.svg`

### Step 3: Add to Project

```bash
# Add your SVG files to the brand-names directory
cp path/to/googledrive.svg assets/brand-names/
cp path/to/notion.svg assets/brand-names/
cp path/to/figma.svg assets/brand-names/
```

### Step 4: Generate CSS

```bash
npm run generateBrandsCSS
```

This command:
- Scans `assets/brand-names/` for SVG files
- Generates CSS rules in `assets/css/brands.css`
- Makes the brands available to bookmarks

### Step 5: Verify

Check the admin panel or run the CLI script again to verify coverage improved.

## Best Practices

### Prioritize High-Impact Brands

Focus on creating logos for:
1. **Most frequently used bookmarks** (shown in CLI report)
2. **Core services** your users rely on
3. **Well-known brands** that are easily recognizable

### Maintain Consistent Style

- Use SVG format for scalability
- Keep file sizes small (< 5KB recommended)
- Use consistent dimensions
- Follow brand guidelines when available

### Regular Monitoring

- Check brand coverage quarterly
- Review after major user growth
- Update when new popular services emerge

## Troubleshooting

### "No bookmarks found"
- Verify users have created bookmarks
- Check that user files exist in `site/accounts/`
- Ensure bookmarks field is properly configured

### "No brands found"
- Verify `assets/css/brands.css` exists
- Check that brands.css has the expected format
- Try regenerating with `npm run generateBrandsCSS`

### Coverage not improving after adding logos
- Verify SVG files are in `assets/brand-names/`
- Ensure you ran `npm run generateBrandsCSS`
- Check browser cache (hard refresh in admin panel)
- Verify brand names match bookmark titles (case-insensitive)

### CLI script errors
- Ensure Node.js is installed
- Check file permissions
- Verify you're in the correct directory

## Technical Details

### Files Modified

- `site/plugins/panel-stats/index.php` - Added brand coverage methods
- `site/blueprints/site.yml` - Added brand statistics display
- `scripts/check-brand-coverage.js` - New CLI analysis tool
- `package.json` - Added `check-brands` npm script

### Site Methods Added

- `site()->availableBrands()` - Returns array of available brand names
- `site()->totalAvailableBrands()` - Returns count of available brands
- `site()->bookmarksWithoutBrands()` - Returns count without brands
- `site()->brandCoveragePercentage()` - Returns coverage percentage

## Support

For issues or questions:
- Check the [brainstorming document](brand-name-testing-brainstorm.md) for context
- Review the [panel-stats plugin README](../site/plugins/panel-stats/README.md)
- Contact the development team
