# Panel Stats Plugin

This plugin provides dynamic statistics for the Kirby CMS panel dashboard.

## Features

The plugin adds the following site methods that can be used in panel blueprints:

### User Statistics
- `site.totalUsers()` - Returns the total number of users
- `site.freeUsers()` - Returns the count of free users (users with no tier or "Free" tier)
- `site.paidUsers()` - Returns the count of paid users (Basic, Premium, or other paid tiers)
- `site.paidUsersPercentage()` - Returns the percentage of paid users as a formatted string (e.g., "50%")
- `site.freeUsersPercentage()` - Returns the percentage of free users
- `site.inactiveUsers()` / `inactiveUsersInfo()` / `inactiveUsersTheme()` - Inactive free accounts, same rules as the "Inactive accounts" dialog (`site/plugins/account-cleanup`); the tile opens that dialog

### Bookmark Statistics
- `site.totalBookmarks()` - Returns the total number of bookmarks across all users
- `site.totalTags()` - Returns the count of unique tags (case-insensitive) across all bookmarks
- `site.bookmarksPerUser()` - Average bookmarks per user with bookmarks (e.g. "Ø 10.7 per user with bookmarks")
- `site.usersWithBookmarks()` - Users with at least one bookmark (activation chart)
- `site.taggedBookmarks()` / `taggedBookmarksInfo()` - Bookmarks with at least one tag and their share of all bookmarks

### Brand Coverage Statistics
- `site.availableBrands()` - Returns array of available brand tokens (logos in `assets/brand-names`, see `site/plugins/brands`)
- `site.totalAvailableBrands()` - Returns the count of available brand logos
- `site.bookmarksWithoutBrands()` - Returns count of bookmarks without matching brand logos
- `site.brandCoveragePercentage()` - Returns brand coverage percentage as formatted string (e.g., "85.5%")
- `site.missingBrandsList()` - Returns detailed array of bookmarks without brands
- `site.missingBrandsText()` - Returns formatted text list of all missing brands for display
- `site.missingBrandsReports()` - Missing brands as stat reports (grouped by suggested file name, most users first)
- `site.brandsInUse()` / `brandsInUseInfo()` - Number of different logos used by bookmarks
- `site.brandCoverageInfo()` / `brandCoverageTheme()` / `bookmarksWithoutBrandsTheme()` - Details and color (positive ≥ 90 %, notice ≥ 75 %, negative below) for the brand tiles
- `site.missingBrandsClipboard()` - Missing brands as plain text, one line per logo, for the "Copy list" button

### Dashboard views
The statistics live in two Panel views (admins only), each laid out by its own blueprint:
- **Bookmarks** (own menu entry, `site/blueprints/dashboard/bookmarks.yml`) - bookmark, tag and brand statistics
- **Users › Statistics** (`site/blueprints/dashboard/users.yml`) - user statistics, opened with the "Statistics" button in the users list; the Users menu entry stays highlighted

Sections query the site as before (`site.totalBookmarks` etc.) and load from the API at `panel-stats/<dashboard>/sections/<section>`. Menu order, the "Statistics" button and the highlighting are set in `site/config/config.php` (`panel.menu`, `panel.viewButtons.users`).

### Stats with buttons
A custom `actionstats` section works like Kirby's `stats` section and adds buttons to its header. A button either copies the text of a query to the clipboard (`copy`) or opens a Panel dialog (`dialog`):

```yaml
MissingBrands:
  type: actionstats
  reports: site.missingBrandsReports
  empty: Every bookmark has a logo
  buttons:
    - text: Copy list
      icon: copy
      copy: site.missingBrandsClipboard
```

### Charts
A custom `chart` section (`index.js`, `index.css`) draws a bar list (`layout: bars`, default) or one stacked bar with legend (`layout: stack`). `data` is a site method returning items with `label`, `value` and optional `info`, `color` (`series-1`…`series-3`, `good`, `warning`, `critical`) and `image`:
- `site.userMixChart()` - Paid / free active / free inactive
- `site.activationChart()` - Registered → saved bookmarks → uses tags (share of all users; paid users are a tile)
- `site.activityChart()` - Users by last activity
- `site.topTagsChart()` / `site.topBrandsChart()` - Top 8 tags and logos by number of users, then by number of bookmarks ("2 users · 51×")
- `site.brandCoverageChart()` - Bookmarks with / without logo

```yaml
UserMix:
  type: chart
  headline: User Mix
  layout: stack
  data: site.userMixChart
```

## Usage

These methods are used in the dashboard blueprints to display dynamic statistics (simplified example, see `site/blueprints/dashboard/*.yml` for the full dashboards with icons, themes and info lines):

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

Bookmarks are matched against available brands using the bookmark's link and title (`BrandLogos::find()` in `site/plugins/brands`). Every logo file name is reduced to lowercase letters a-z (its "token"):
1. **Link:** a label of the domain equals a token, e.g. `app.slack.com` matches `slack.svg`. Domains that differ from the brand name are listed in `BrandLogos::ALIASES` (`bahn.de` matches `deutschebahn.svg`)
2. **Title:** a token equals one or more whole consecutive words, e.g. "Buy me a coffee" matches `buymeacoffee.svg`, "Otherwise" never matches `wise.svg`. Camel case counts as separate words ("DuckDuckGo")
3. Everyday words (`BrandLogos::DOMAIN_ONLY`, e.g. `medium`, `web`, `dev`) match via the link only
4. The longest token wins, the first one on a tie

### Viewing Brand Statistics

1. Log in to the Kirby admin panel
2. Navigate to the Site section
3. View the "Brands" statistics panel for overview
4. Scroll down to "Missing Brand Logos" section to see detailed list

The panel shows:
- Brand coverage statistics (count and percentage)
- Complete list of all bookmark titles without brands (sorted by usage frequency)
- Usage count for each missing brand
- Suggested brand name (lowercase, no spaces)

### Improving Brand Coverage

To improve brand coverage:
1. Check the "Missing Brand Logos" section in the admin panel
2. Create SVG logos for the most frequently used bookmarks without brands
3. Add them to `assets/brand-names/` directory with the suggested name
4. No build step needed – new SVGs are picked up automatically
5. Refresh the admin panel to see updated statistics

## Dependencies

- Requires the `kreativ-anders.memberkit` plugin for tier management
- Requires user accounts with the `tier` field
- Requires users to have a `bookmarks` field that returns YAML data
- Requires the `brands` plugin (`site/plugins/brands`) for brand coverage analysis

## Integration with Stripe

This plugin is compatible with Stripe integration via the memberkit plugin. It distinguishes between free and paid users based on the tier configuration in `site/config/config.php`.

