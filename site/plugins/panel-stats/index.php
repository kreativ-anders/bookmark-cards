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

Kirby::plugin('kreativ-anders/panel-stats', [
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
