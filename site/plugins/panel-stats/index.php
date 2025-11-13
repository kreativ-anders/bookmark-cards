<?php

/**
 * Panel Stats Plugin
 * Provides dynamic statistics for the Kirby panel
 */

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
            // Parse brands.css to get list of available brand names
            $brandsFile = kirby()->root('assets') . '/css/brands.css';
            $brands = [];
            
            if (file_exists($brandsFile)) {
                $content = file_get_contents($brandsFile);
                // Match patterns like *[brand~='brandname']
                preg_match_all("/\*\[brand~='([^']+)'\]/", $content, $matches);
                if (!empty($matches[1])) {
                    $brands = $matches[1];
                }
            }
            
            return $brands;
        },
        'bookmarksWithoutBrands' => function () {
            $availableBrands = site()->availableBrands();
            $withoutBrands = 0;
            
            foreach (kirby()->users() as $user) {
                $bookmarks = $user->bookmarks()->yaml();
                if (is_array($bookmarks)) {
                    foreach ($bookmarks as $bookmark) {
                        if (!empty($bookmark['title'])) {
                            $title = strtolower($bookmark['title']);
                            $titleNoSpaces = str_replace(' ', '', $title);
                            
                            // Check if brand exists for this bookmark
                            $hasBrand = false;
                            foreach ($availableBrands as $brand) {
                                if ($brand === $title || $brand === $titleNoSpaces) {
                                    $hasBrand = true;
                                    break;
                                }
                            }
                            
                            if (!$hasBrand) {
                                $withoutBrands++;
                            }
                        }
                    }
                }
            }
            
            return $withoutBrands;
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
        'totalAvailableBrands' => function () {
            return count(site()->availableBrands());
        },
        'missingBrandsList' => function () {
            $availableBrands = site()->availableBrands();
            $missingBrands = [];
            
            foreach (kirby()->users() as $user) {
                $bookmarks = $user->bookmarks()->yaml();
                if (is_array($bookmarks)) {
                    foreach ($bookmarks as $bookmark) {
                        if (!empty($bookmark['title'])) {
                            $title = strtolower($bookmark['title']);
                            $titleNoSpaces = str_replace(' ', '', $title);
                            
                            // Check if brand exists for this bookmark
                            $hasBrand = false;
                            foreach ($availableBrands as $brand) {
                                if ($brand === $title || $brand === $titleNoSpaces) {
                                    $hasBrand = true;
                                    break;
                                }
                            }
                            
                            if (!$hasBrand) {
                                // Track unique bookmark titles without brands
                                if (!isset($missingBrands[$bookmark['title']])) {
                                    $missingBrands[$bookmark['title']] = [
                                        'title' => $bookmark['title'],
                                        'count' => 0,
                                        'suggested' => $titleNoSpaces
                                    ];
                                }
                                $missingBrands[$bookmark['title']]['count']++;
                            }
                        }
                    }
                }
            }
            
            // Sort by count (most used first)
            usort($missingBrands, function($a, $b) {
                return $b['count'] - $a['count'];
            });
            
            return $missingBrands;
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
