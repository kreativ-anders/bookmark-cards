<?php

/**
 * Panel Stats Plugin
 * Provides dynamic statistics for the Kirby panel
 */

/**
 * All bookmarks of all users as [user id => bookmarks]
 */
function panelStatsBookmarks(): array
{
    $all = [];

    foreach (kirby()->users() as $user) {
        $bookmarks = $user->bookmarks()->yaml();
        $all[$user->id()] = is_array($bookmarks) ? $bookmarks : [];
    }

    return $all;
}

/**
 * Bookmark titles shown as stat labels: never evaluate {{ queries }} from user input
 */
function panelStatsLabel(string $value): string
{
    return str_replace(['{{', '}}'], ['{ {', '} }'], $value);
}

function panelStatsPercentage(int|float $part, int|float $total, string $empty = '0%'): string
{
    return $total > 0 ? round(($part / $total) * 100, 1) . '%' : $empty;
}

/**
 * Chart item for the `chart` section (index.js)
 * color: series-1…3 (categorical) or good/warning/critical (status)
 */
function panelStatsBar(string $label, int $value, string|null $info = null, string $color = 'series-1', string|null $image = null): array
{
    return compact('label', 'value', 'info', 'color', 'image');
}

/**
 * Chart bars of the most widespread values: [name => [user id => bookmark count]]
 * ranked by number of users, then by number of bookmarks, then alphabetical.
 * The bar shows the users, the info how often they used it ("2 users · 51×").
 *
 * @param callable|null $image name => image URL
 */
function panelStatsUsage(array $usage, callable|null $image = null, int $limit = 8): array
{
    $rows = [];
    foreach ($usage as $name => $users) {
        $rows[] = ['name' => (string)$name, 'users' => count($users), 'count' => array_sum($users)];
    }

    usort($rows, fn ($a, $b) => [$b['users'], $b['count'], $a['name']] <=> [$a['users'], $a['count'], $b['name']]);

    return array_map(fn ($row) => panelStatsBar(
        $row['name'],
        $row['users'],
        ($row['users'] === 1 ? 'user' : 'users') . ' · ' . $row['count'] . '×',
        'series-1',
        $image ? $image($row['name']) : null
    ), array_slice($rows, 0, $limit));
}

/**
 * Blueprint of a dashboard view (site/blueprints/dashboard/<name>.yml),
 * its sections query the site. Null for unknown dashboards.
 */
function panelStatsDashboard(string $name): Kirby\Cms\Blueprint|null
{
    if (in_array($name, ['bookmarks', 'users'], true) === false) {
        return null;
    }

    return Kirby\Cms\Blueprint::factory('dashboard/' . $name, null, site());
}

/**
 * Panel view of a dashboard: its sections, laid out like a site tab (admins only)
 */
function panelStatsView(string $name, array $buttons = []): array
{
    if (kirby()->user()?->isAdmin() !== true) {
        throw new Kirby\Exception\PermissionException(message: 'Only admins can see the statistics');
    }

    $blueprint = panelStatsDashboard($name);

    return [
        'component' => 'k-dashboard-view',
        'title'     => $blueprint->title(),
        'props'     => [
            'title'   => $blueprint->title(),
            'parent'  => 'panel-stats/' . $name,
            'tab'     => $blueprint->tab(),
            'buttons' => $buttons,
        ],
    ];
}

$panelStatsAdmin = fn () => kirby()->user()?->isAdmin() === true;

Kirby::plugin('kreativ-anders/panel-stats', [
    'areas' => [
        // own menu entry for bookmark and brand statistics
        'bookmarks' => fn () => [
            'label' => 'Bookmarks',
            'icon'  => 'bookmark',
            'menu'  => $panelStatsAdmin,
            'link'  => 'bookmarks',
            'views' => [
                [
                    'pattern' => 'bookmarks',
                    'action'  => fn () => panelStatsView('bookmarks'),
                ],
            ],
        ],
        // user statistics, shown as "Users › Statistics" with the Users menu entry highlighted
        'user-statistics' => fn () => [
            'label' => 'Users',
            'icon'  => 'users',
            'menu'  => false,
            'link'  => 'users',
            'views' => [
                [
                    'pattern' => 'user-statistics',
                    'action'  => fn () => [
                        ...panelStatsView('users', [
                            ['icon' => 'users', 'text' => 'All users', 'link' => 'users'],
                        ]),
                        'breadcrumb' => [
                            ['label' => 'Statistics', 'link' => 'user-statistics'],
                        ],
                    ],
                ],
            ],
        ],
        // "Statistics" button in the users list (panel.viewButtons.users in config.php),
        // the Users menu entry stays highlighted via panel.menu in config.php
        'users' => fn () => [
            'buttons' => [
                'users.statistics' => fn () => [
                    'icon' => 'chart',
                    'text' => 'Statistics',
                    'link' => 'user-statistics',
                ],
            ],
        ],
    ],
    'api' => [
        'routes' => [
            [
                // sections of the dashboard views (k-sections loads "<parent>/sections/<name>")
                'pattern' => 'panel-stats/(:any)/sections/(:any)',
                'method'  => 'GET',
                'action'  => function (string $dashboard, string $section) {
                    if ($this->user()?->isAdmin() !== true) {
                        throw new Kirby\Exception\PermissionException(message: 'Only admins can see the statistics');
                    }

                    return panelStatsDashboard($dashboard)?->section($section)?->toResponse();
                },
            ],
        ],
    ],
    'sections' => [
        // Kirby's stats section plus header buttons (see site.yml):
        // `copy` copies the text of a query to the clipboard, `dialog` opens a Panel dialog
        'actionstats' => [
            'extends' => 'stats',
            'props'   => [
                'buttons' => fn (array $buttons = []) => $buttons,
                'empty'   => fn (string|null $empty = null) => $empty,
            ],
            'computed' => [
                'buttons' => function () {
                    return array_values(array_map(fn (array $button) => [
                        'text'   => $button['text'] ?? null,
                        'icon'   => $button['icon'] ?? null,
                        'copy'   => isset($button['copy']) ? (string)$this->model()->query($button['copy']) : null,
                        'dialog' => $button['dialog'] ?? null,
                    ], $this->buttons));
                },
            ],
        ],
        // bar list or single stacked bar, data from a site method (see site.yml)
        'chart' => [
            'props' => [
                'headline' => fn ($headline = null) => $headline,
                'layout'   => fn (string $layout = 'bars') => $layout === 'stack' ? 'stack' : 'bars',
                'data'     => fn ($data = []) => $data,
                'empty'    => fn (string $empty = 'No data yet') => $empty,
            ],
            'computed' => [
                'data' => function () {
                    $data = is_string($this->data) ? $this->model()->query($this->data) : $this->data;

                    return is_array($data) ? array_values($data) : [];
                },
            ],
        ],
    ],
    'siteMethods' => [
        'totalUsers' => function () {
            return kirby()->users()->count();
        },
        'freeUsers' => function () {
            $freeCount = 0;
            $freeTier = option('kreativ-anders.memberkit.tiers')[0]['name'];

            foreach (kirby()->users() as $user) {
                $userTier = $user->tier()->toString();
                if (empty($userTier) || $userTier === $freeTier) {
                    $freeCount++;
                }
            }

            return $freeCount;
        },
        'paidUsers' => function () {
            $paidCount = 0;
            $freeTier = option('kreativ-anders.memberkit.tiers')[0]['name'];

            foreach (kirby()->users() as $user) {
                $userTier = $user->tier()->toString();
                if (!empty($userTier) && $userTier !== $freeTier) {
                    $paidCount++;
                }
            }

            return $paidCount;
        },
        'freeUsersPercentage' => function () {
            return panelStatsPercentage(site()->freeUsers(), site()->totalUsers());
        },
        'inactiveUsers' => function () {
            // see site/plugins/account-cleanup
            return class_exists('AccountActivity') ? count(AccountActivity::inactive()) : 0;
        },
        'inactiveUsersInfo' => function () {
            $months = class_exists('AccountActivity') ? AccountActivity::months() : 12;

            return 'Free, no activity for ' . $months . '+ months';
        },
        'inactiveUsersTheme' => function () {
            return site()->inactiveUsers() > 0 ? 'notice' : 'positive';
        },
        'totalBookmarks' => function () {
            $totalBookmarks = 0;

            foreach (kirby()->users() as $user) {
                $bookmarks = $user->bookmarks()->yaml();
                if (is_array($bookmarks)) {
                    $totalBookmarks += count($bookmarks);
                }
            }

            return $totalBookmarks;
        },
        // average over users who saved bookmarks (the share of those users is in the activation chart)
        'bookmarksPerUser' => function () {
            $users = site()->usersWithBookmarks();
            $average = $users > 0 ? round(site()->totalBookmarks() / $users, 1) : 0;

            return 'Ø ' . $average . ' per user with bookmarks';
        },
        'usersWithBookmarks' => function () {
            return count(array_filter(panelStatsBookmarks()));
        },
        'totalTags' => function () {
            $allTags = [];

            foreach (kirby()->users() as $user) {
                $bookmarks = $user->bookmarks()->yaml();
                if (is_array($bookmarks)) {
                    foreach ($bookmarks as $bookmark) {
                        if (!empty($bookmark['tags'])) {
                            $tags = array_map('trim', explode(',', $bookmark['tags']));
                            $allTags = array_merge($allTags, $tags);
                        }
                    }
                }
            }

            // Return count of unique tags (case-insensitive)
            return count(array_unique(array_map('strtolower', $allTags)));
        },
        'taggedBookmarks' => function () {
            $tagged = 0;

            foreach (panelStatsBookmarks() as $bookmarks) {
                foreach ($bookmarks as $bookmark) {
                    if (trim((string)($bookmark['tags'] ?? ''), ' ,') !== '') {
                        $tagged++;
                    }
                }
            }

            return $tagged;
        },
        'taggedBookmarksInfo' => function () {
            return panelStatsPercentage(site()->taggedBookmarks(), site()->totalBookmarks()) . ' of all bookmarks';
        },
        'paidUsersPercentage' => function () {
            $total = site()->totalUsers();
            if ($total === 0) {
                return '0%';
            }

            $paid = site()->paidUsers();
            $percentage = round(($paid / $total) * 100, 1);

            return $percentage . '%';
        },
        'availableBrands' => function () {
            // Tokens of all logos in assets/brand-names (see site/plugins/brands)
            return array_keys(BrandLogos::all());
        },
        'brandsInUse' => function () {
            $used = [];

            foreach (panelStatsBookmarks() as $bookmarks) {
                foreach ($bookmarks as $bookmark) {
                    if (($token = BrandLogos::find((string)($bookmark['title'] ?? ''), (string)($bookmark['link'] ?? ''))) !== null) {
                        $used[$token] = true;
                    }
                }
            }

            return count($used);
        },
        'brandsInUseInfo' => function () {
            return site()->brandsInUse() . ' in use';
        },
        'bookmarksWithoutBrands' => function () {
            $withoutBrands = 0;

            foreach (kirby()->users() as $user) {
                $bookmarks = $user->bookmarks()->yaml();
                if (is_array($bookmarks)) {
                    foreach ($bookmarks as $bookmark) {
                        if (!empty($bookmark['title']) && BrandLogos::find($bookmark['title'], (string)($bookmark['link'] ?? '')) === null) {
                            $withoutBrands++;
                        }
                    }
                }
            }

            return $withoutBrands;
        },
        'bookmarksWithoutBrandsTheme' => function () {
            return site()->bookmarksWithoutBrands() > 0 ? 'notice' : 'positive';
        },
        'brandCoveragePercentage' => function () {
            $total = site()->totalBookmarks();
            if ($total === 0) {
                return '100%';
            }

            $without = site()->bookmarksWithoutBrands();
            $withBrands = $total - $without;
            $percentage = round(($withBrands / $total) * 100, 1);

            return $percentage . '%';
        },
        'brandCoverageInfo' => function () {
            $total = site()->totalBookmarks();

            return ($total - site()->bookmarksWithoutBrands()) . ' of ' . $total . ' bookmarks';
        },
        'brandCoverageTheme' => function () {
            $percentage = (float)site()->brandCoveragePercentage();

            return match (true) {
                $percentage >= 90 => 'positive',
                $percentage >= 75 => 'notice',
                default           => 'negative',
            };
        },
        'totalAvailableBrands' => function () {
            return count(site()->availableBrands());
        },
        'missingBrandsList' => function () {
            $missingBrands = [];

            foreach (panelStatsBookmarks() as $userId => $bookmarks) {
                foreach ($bookmarks as $bookmark) {
                    if (empty($bookmark['title']) || BrandLogos::find($bookmark['title'], (string)($bookmark['link'] ?? '')) !== null) {
                        continue;
                    }

                    // file name that would match: assets/brand-names/<suggested>.svg
                    $suggested = BrandLogos::token($bookmark['title']);

                    // "Spotify" and "spotify" need the same logo => group by token
                    $key = $suggested !== '' ? $suggested : $bookmark['title'];

                    $missingBrands[$key] ??= [
                        'title'     => $bookmark['title'],
                        'count'     => 0,
                        'suggested' => $suggested,
                        'users'     => [],
                    ];
                    $missingBrands[$key]['count']++;
                    $missingBrands[$key]['users'][$userId] = true;
                }
            }

            $missingBrands = array_map(
                fn ($item) => [...$item, 'users' => count($item['users'])],
                array_values($missingBrands)
            );

            // a logo used by many people helps more than one used often by a single person
            usort($missingBrands, fn ($a, $b) => [$b['users'], $b['count']] <=> [$a['users'], $a['count']]);

            return $missingBrands;
        },
        // plain text for the clipboard (section "actionstats"): one line per missing logo
        'missingBrandsClipboard' => function () {
            return implode("\n", array_map(fn ($item) => $item['title']
                . ($item['suggested'] !== '' ? ' -> ' . $item['suggested'] . '.svg' : '')
                . ' (' . $item['count'] . '×, ' . $item['users'] . ($item['users'] === 1 ? ' user' : ' users') . ')',
                site()->missingBrandsList()));
        },
        'missingBrandsReports' => function () {
            return array_map(fn ($item) => [
                'label' => $item['suggested'] !== '' ? 'add ' . $item['suggested'] . '.svg' : 'no letters in title',
                'value' => panelStatsLabel($item['title']),
                'info'  => $item['count'] . '× · ' . $item['users'] . ($item['users'] === 1 ? ' user' : ' users'),
                'icon'  => 'image',
            ], site()->missingBrandsList());
        },
        'userMixChart' => function () {
            $paid     = site()->paidUsers();
            $inactive = site()->inactiveUsers();

            return [
                panelStatsBar('Paid', $paid, null, 'series-1'),
                panelStatsBar('Free, active', max(0, site()->freeUsers() - $inactive), null, 'series-3'),
                panelStatsBar('Free, inactive', $inactive, null, 'series-2'),
            ];
        },
        'activityChart' => function () {
            $buckets = ['Last 7 days' => 7, '8–30 days' => 30, '1–3 months' => 91, '3–12 months' => 365, 'Over 12 months' => PHP_INT_MAX];
            $counts  = array_fill_keys(array_keys($buckets), 0);

            foreach (kirby()->users() as $user) {
                $last = class_exists('AccountActivity') ? AccountActivity::last($user) : (int)$user->modified();
                $days = (time() - $last) / 86400;

                foreach ($buckets as $label => $limit) {
                    if ($days <= $limit) {
                        $counts[$label]++;
                        break;
                    }
                }
            }

            $total = site()->totalUsers();

            return array_map(
                fn ($label) => panelStatsBar($label, $counts[$label], panelStatsPercentage($counts[$label], $total)),
                array_keys($counts)
            );
        },
        'activationChart' => function () {
            $total = site()->totalUsers();
            $tagging = 0;

            foreach (panelStatsBookmarks() as $bookmarks) {
                foreach ($bookmarks as $bookmark) {
                    if (trim((string)($bookmark['tags'] ?? ''), ' ,') !== '') {
                        $tagging++;
                        break;
                    }
                }
            }

            $steps = [
                'Registered'      => $total,
                'Saved bookmarks' => site()->usersWithBookmarks(),
                'Uses tags'       => $tagging,
            ];

            return array_map(
                fn ($label) => panelStatsBar($label, $steps[$label], panelStatsPercentage($steps[$label], $total)),
                array_keys($steps)
            );
        },
        'topTagsChart' => function () {
            $usage = [];

            foreach (panelStatsBookmarks() as $userId => $bookmarks) {
                foreach ($bookmarks as $bookmark) {
                    $tags = array_map(fn ($tag) => Kirby\Toolkit\Str::lower(trim($tag)), explode(',', (string)($bookmark['tags'] ?? '')));

                    foreach (array_unique(array_filter($tags, 'strlen')) as $tag) {
                        $usage[$tag][$userId] = ($usage[$tag][$userId] ?? 0) + 1;
                    }
                }
            }

            return panelStatsUsage($usage);
        },
        'topBrandsChart' => function () {
            $usage = [];

            foreach (panelStatsBookmarks() as $userId => $bookmarks) {
                foreach ($bookmarks as $bookmark) {
                    if (($token = BrandLogos::find((string)($bookmark['title'] ?? ''), (string)($bookmark['link'] ?? ''))) !== null) {
                        $usage[$token][$userId] = ($usage[$token][$userId] ?? 0) + 1;
                    }
                }
            }

            return panelStatsUsage($usage, fn ($token) => BrandLogos::fileUrl(BrandLogos::all()[$token]));
        },
        'brandCoverageChart' => function () {
            $missing = site()->bookmarksWithoutBrands();

            return [
                panelStatsBar('Logo', max(0, site()->totalBookmarks() - $missing), null, 'good'),
                panelStatsBar('No logo', $missing, null, 'critical'),
            ];
        },
        'missingBrandsText' => function () {
            $missing = site()->missingBrandsList();

            if (empty($missing)) {
                return '✅ All bookmarks have matching brand logos!';
            }

            $text = "The following bookmark titles don't have matching brand logos:\n\n";

            // Show all missing brands (no limit)
            foreach ($missing as $item) {
                $text .= "• **{$item['title']}** (used {$item['count']}x)\n";
                $text .= "  Suggested brand name: `{$item['suggested']}`\n\n";
            }

            return $text;
        }
    ]
]);
