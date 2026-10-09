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
 * [name => count] of the most frequent values, ties alphabetical
 */
function panelStatsTop(array $counts, int $limit = 8): array
{
    uksort($counts, fn ($a, $b) => [$counts[$b], $a] <=> [$counts[$a], $b]);

    return array_slice($counts, 0, $limit, true);
}

Kirby::plugin('kreativ-anders/panel-stats', [
    'sections' => [
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
        'bookmarksPerUser' => function () {
            $users = site()->totalUsers();
            $average = $users > 0 ? round(site()->totalBookmarks() / $users, 1) : 0;

            return 'Ø ' . $average . ' per user';
        },
        'usersWithBookmarks' => function () {
            return count(array_filter(panelStatsBookmarks()));
        },
        'usersWithBookmarksInfo' => function () {
            return panelStatsPercentage(site()->usersWithBookmarks(), site()->totalUsers()) . ' of all users';
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
        'taggedBookmarksInfo' => function () {
            $tagged = 0;

            foreach (panelStatsBookmarks() as $bookmarks) {
                foreach ($bookmarks as $bookmark) {
                    if (trim((string)($bookmark['tags'] ?? ''), ' ,') !== '') {
                        $tagged++;
                    }
                }
            }

            return panelStatsPercentage($tagged, site()->totalBookmarks()) . ' of bookmarks tagged';
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
                    if (($token = BrandLogos::find((string)($bookmark['title'] ?? ''))) !== null) {
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
                        if (!empty($bookmark['title']) && BrandLogos::find($bookmark['title']) === null) {
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
                    if (empty($bookmark['title']) || BrandLogos::find($bookmark['title']) !== null) {
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
        'missingBrandsReports' => function () {
            return array_map(fn ($item) => [
                'label' => $item['suggested'] !== '' ? 'add ' . $item['suggested'] . '.svg' : 'no letters in title',
                'value' => panelStatsLabel($item['title']),
                'info'  => $item['count'] . '× · ' . $item['users'] . ($item['users'] === 1 ? ' user' : ' users'),
                'icon'  => 'image',
            ], site()->missingBrandsList());
        },
        /**
         * Bookmarks whose logo matched only a part of the title:
         * possibly a wrong logo ("Webflow" -> web.svg) or room for a more specific one
         */
        'partialBrandMatches' => function () {
            $matches = [];

            foreach (panelStatsBookmarks() as $bookmarks) {
                foreach ($bookmarks as $bookmark) {
                    $title = (string)($bookmark['title'] ?? '');
                    $token = BrandLogos::find($title);

                    if ($token === null || $token === BrandLogos::token($title)) {
                        continue;
                    }

                    $key = BrandLogos::token($title);
                    $matches[$key] ??= [
                        'title' => $title,
                        'logo'  => BrandLogos::all()[$token],
                        'count' => 0,
                    ];
                    $matches[$key]['count']++;
                }
            }

            $matches = array_values($matches);
            usort($matches, fn ($a, $b) => $b['count'] <=> $a['count']);

            return $matches;
        },
        'partialBrandMatchesReports' => function () {
            return array_map(fn ($item) => [
                'label' => 'shows ' . $item['logo'] . ' – correct?',
                'value' => panelStatsLabel($item['title']),
                'info'  => $item['count'] . '×',
                'icon'  => 'search',
            ], site()->partialBrandMatches());
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
                'Paid'            => site()->paidUsers(),
            ];

            return array_map(
                fn ($label) => panelStatsBar($label, $steps[$label], panelStatsPercentage($steps[$label], $total)),
                array_keys($steps)
            );
        },
        'topTagsChart' => function () {
            $counts = [];

            foreach (panelStatsBookmarks() as $bookmarks) {
                foreach ($bookmarks as $bookmark) {
                    $tags = array_map(fn ($tag) => Kirby\Toolkit\Str::lower(trim($tag)), explode(',', (string)($bookmark['tags'] ?? '')));

                    foreach (array_unique(array_filter($tags, 'strlen')) as $tag) {
                        $counts[$tag] = ($counts[$tag] ?? 0) + 1;
                    }
                }
            }

            $top = panelStatsTop($counts);

            return array_map(fn ($tag) => panelStatsBar($tag, $top[$tag]), array_map('strval', array_keys($top)));
        },
        'topBrandsChart' => function () {
            $counts = [];

            foreach (panelStatsBookmarks() as $bookmarks) {
                foreach ($bookmarks as $bookmark) {
                    if (($token = BrandLogos::find((string)($bookmark['title'] ?? ''))) !== null) {
                        $counts[$token] = ($counts[$token] ?? 0) + 1;
                    }
                }
            }

            $top = panelStatsTop($counts);

            return array_map(
                fn ($token) => panelStatsBar($token, $top[$token], null, 'series-1', BrandLogos::fileUrl(BrandLogos::all()[$token])),
                array_map('strval', array_keys($top))
            );
        },
        'brandCoverageChart' => function () {
            $partial = array_sum(array_column(site()->partialBrandMatches(), 'count'));
            $missing = site()->bookmarksWithoutBrands();
            $exact   = max(0, site()->totalBookmarks() - $missing - $partial);

            return [
                panelStatsBar('Exact logo', $exact, null, 'good'),
                panelStatsBar('Partial match', $partial, null, 'warning'),
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
