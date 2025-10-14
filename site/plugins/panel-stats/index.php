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
        }
    ]
]);
