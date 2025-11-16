<?php

/**
 * Home controller
 *
 * Responsible for serving bookmarks and handling simple CRUD via POST requests.
 * This controller keeps backward compatible behavior but applies defensive checks
 * and clearer logic for maintainability.
 *
 * @param \\Kirby\Cms\App $kirby
 * @param \\Kirby\Cms\Page $page
 * @return array
 */
return function ($kirby, $page) {

  $error = null;

  // current user (if logged in)
  $user = $kirby->user();

  // choose bookmarks from user (if exists) otherwise from page
  $bookmarks = $user ? $user->bookmarks()->yaml() : $page->bookmarks()->yaml();
  $bookmarks = is_array($bookmarks) ? $bookmarks : [];

  // collect tags (from update or create inputs). Keep behavior but make it clearer
  $uTags = get('u_tags', '');
  $cTags = get('c_tags', '');

  if ($uTags !== '' || $cTags !== '') {
    $raw = Str::split($uTags . $cTags, ',');
    $raw = is_array($raw) ? $raw : [];

    // normalize, trim and dedupe case-insensitively while preserving first-seen case
    $seen = [];
    $unique = [];
    foreach ($raw as $t) {
      $t = trim((string)$t);
      if ($t === '') continue;
      $lk = mb_strtolower($t);
      if (!isset($seen[$lk])) {
        $seen[$lk] = true;
        $unique[] = $t;
      }
    }
    $tags = A::join($unique);
  } else {
    $tags = '';
  }

  // handle POST actions only for authenticated users
  if ($user && $kirby->request()->is('POST')) {

    // UpdateBookmark: expects u_id (index), u_title and u_link
    $uId = get('u_id');
    $uTitle = get('u_title');
    $uLink = get('u_link');

    if ($uId !== null && $uTitle && $uLink) {
      $bookmarks = $user->bookmarks()->yaml() ?: [];
      $index = (int)$uId;
      // build replacement entry
      $replacement = [
        $index => [
          'title' => (string)$uTitle,
          'link'  => (string)$uLink,
          'tags'  => $tags
        ]
      ];
      $bookmarks = array_replace($bookmarks, $replacement);
      try {
        $user->update(['Bookmarks' => Yaml::encode($bookmarks)]);
      } catch (\Exception $e) {
        $error = $e->getMessage();
      }
    }

    // AddBookmark: expects c_title and c_link
    $cTitle = get('c_title');
    $cLink = get('c_link');
    if ($cTitle && $cLink) {
      $bookmarks = $user->bookmarks()->yaml() ?: [];

      // check premium limit safely
      $tiers = option('kreativ-anders.memberkit.tiers', []);
      $firstTierName = $tiers[0]['name'] ?? null;
      $userTier = method_exists($user, 'tier') ? (string)$user->tier() : null;

      if ($firstTierName !== null && $firstTierName === $userTier && count($bookmarks) >= (int)option('noPremiumLimit')) {
        $entry = [
          'title' => option('noPremiumTitle'),
          'link'  => option('noPremiumLink'),
          'tags'  => option('noPremiumTags')
        ];
      } else {
        $entry = [
          'title' => (string)$cTitle,
          'link'  => (string)$cLink,
          'tags'  => $tags
        ];
      }

      $bookmarks[] = $entry;
      try {
        $user->update(['Bookmarks' => Yaml::encode($bookmarks)]);
      } catch (\Exception $e) {
        $error = $e->getMessage();
      }
    }

    // DeleteBookmark: expects d_bookmark to be a numeric index
    $dIndex = get('d_bookmark');
    if (is_numeric($dIndex)) {
      $bookmarks = $user->bookmarks()->yaml() ?: [];
      $idx = (int)$dIndex;
      if ($idx >= 0 && isset($bookmarks[$idx])) {
        array_splice($bookmarks, $idx, 1);
        try {
          $user->update(['Bookmarks' => Yaml::encode($bookmarks)]);
        } catch (\Exception $e) {
          $error = $e->getMessage();
        }
      }
    }
  }

  return [
    'error' => $error,
    'bookmarks' => $bookmarks
  ];
};