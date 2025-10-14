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
```

## Dependencies

- Requires the `kreativ-anders.memberkit` plugin for tier management
- Requires user accounts with the `tier` field
- Requires users to have a `bookmarks` field that returns YAML data

## Integration with Stripe

This plugin is compatible with Stripe integration via the memberkit plugin. It distinguishes between free and paid users based on the tier configuration in `site/config/config.php`.
