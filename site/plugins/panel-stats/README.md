# Panel Stats Plugin

This plugin provides dynamic statistics for the Kirby CMS panel dashboard.

## Features

The plugin adds the following site methods that can be used in panel blueprints:

### User Statistics
- `site.totalUsers()` - Returns the total number of users
- `site.freeUsers()` - Returns the count of free users (users with no tier or "Free" tier)
- `site.paidUsers()` - Returns the count of paid users (Basic, Premium, or other paid tiers)
- `site.paidUsersPercentage()` - Returns the percentage of paid users as a formatted string (e.g., "50%")

### Bookmark Statistics
- `site.totalBookmarks()` - Returns the total number of bookmarks across all users
- `site.totalTags()` - Returns the count of unique tags (case-insensitive) across all bookmarks

### Brand Coverage Statistics
- `site.availableBrands()` - Returns array of available brand names from brands.css
- `site.totalAvailableBrands()` - Returns the count of available brand logos
- `site.bookmarksWithoutBrands()` - Returns count of bookmarks without matching brand logos
- `site.brandCoveragePercentage()` - Returns brand coverage percentage as formatted string (e.g., "85.5%")
- `site.missingBrandsList()` - Returns detailed array of bookmarks without brands
- `site.missingBrandsText()` - Returns formatted text list of missing brands for display

## Usage

These methods are used in the `site.yml` blueprint to display dynamic statistics on the panel dashboard:

```yaml
sections:
  Users:
    type: stats
    size: huge
    reports:
      - label: Total Users
        value: "{{ site.totalUsers }}"
      - label: Free Users
        value: "{{ site.freeUsers }}"
      - label: Paid Users
        value: "{{ site.paidUsers }}"
        info: "{{ site.paidUsersPercentage }}"
        
  Bookmarks:
    type: stats
    size: medium
    reports:
      - label: Total Bookmarks
        value: "{{ site.totalBookmarks }}"
      - label: Total Tags
        value: "{{ site.totalTags }}"
        
  Brands:
    type: stats
    size: medium
    reports:
      - label: Available Brands
        value: "{{ site.totalAvailableBrands }}"
      - label: Brand Coverage
        value: "{{ site.brandCoveragePercentage }}"
      - label: Bookmarks Without Brands
        value: "{{ site.bookmarksWithoutBrands }}"
        
  MissingBrands:
    headline: Missing Brand Logos
    type: info
    text: "{{ site.missingBrandsText }}"
```

## Brand Coverage Feature

The brand coverage feature helps administrators identify which bookmarks don't have matching brand logos directly in the admin panel.

### How Brand Matching Works

Bookmarks are matched against available brands using the bookmark's title:
1. The title is converted to lowercase
2. The system checks for an exact match with brand names in `brands.css`
3. The system also checks the title with spaces removed

For example, a bookmark titled "Google Drive" would match:
- `google drive` (exact match)
- `googledrive` (no spaces)

### Viewing Brand Statistics

1. Log in to the Kirby admin panel
2. Navigate to the Site section
3. View the "Brands" statistics panel for overview
4. Scroll down to "Missing Brand Logos" section to see detailed list

The panel shows:
- Brand coverage statistics (count and percentage)
- Detailed list of bookmark titles without brands
- Usage count for each missing brand
- Suggested brand name (lowercase, no spaces)

### Improving Brand Coverage

To improve brand coverage:
1. Check the "Missing Brand Logos" section in the admin panel
2. Create SVG logos for the most frequently used bookmarks without brands
3. Add them to `assets/brand-names/` directory with the suggested name
4. Run `npm run generateBrandsCSS` to update brands.css
5. Refresh the admin panel to see updated statistics

## Dependencies

- Requires the `kreativ-anders.memberkit` plugin for tier management
- Requires user accounts with the `tier` field
- Requires users to have a `bookmarks` field that returns YAML data
- Requires `assets/css/brands.css` for brand coverage analysis

## Integration with Stripe

This plugin is compatible with Stripe integration via the memberkit plugin. It distinguishes between free and paid users based on the tier configuration in `site/config/config.php`.

