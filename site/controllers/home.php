<?php

/**
 * Home controller
 *
 * Serves the bookmarks and handles add/update/delete via POST.
 * Exactly one action per request; validation and storage helpers live in
 * site/plugins/bookmarks. Successful POSTs redirect (POST/REDIRECT/GET),
 * so a reload never submits the form twice.
 *
 * @param \Kirby\Cms\App $kirby
 * @param \Kirby\Cms\Page $page
 * @return array
 */
return function ($kirby, $page) {

  $error = null;
  $event = null;

  // current user (if logged in)
  $user = $kirby->user();

  // last visit, for the inactive accounts clean-up in the Panel (site/plugins/account-cleanup)
  if ($user) {
    AccountActivity::touch($user);
  }

  // POST actions (add/update/delete) for authenticated users, require a valid CSRF token
  if ($user && $kirby->request()->is('POST')) {

    if (csrf(get('csrf')) !== true) {

      $error = 'Invalid CSRF token! Please reload the page and try again.';

    } else {

      $tiers       = option('kreativ-anders.memberkit.tiers', []);
      $isFree      = isset($tiers[0]['name']) && $tiers[0]['name'] === $user->tier()->toString();
      $freeLimit   = (int)option('noPremiumLimit');
      $placeholder = option('noPremiumTitle');

      // validated entry from the create (c_*) or update (u_*) form
      $entry = function (string $prefix) use (&$error): array|null {
        $title = Bookmarks::input($prefix . '_title') ?? '';
        $link  = is_string(get($prefix . '_link')) ? get($prefix . '_link') : '';
        $tags  = Bookmarks::input($prefix . '_tags') ?? '';

        if (mb_strlen($title) > Bookmarks::MAX_TITLE || mb_strlen($link) > Bookmarks::MAX_LINK || mb_strlen($tags) > Bookmarks::MAX_TAGS) {
          $error = 'Title, link or tags are too long!';
          return null;
        }

        $link = Bookmarks::link($link);

        if ($title === '' || $link === null) {
          $error = 'Please enter a title and a valid web link!';
          return null;
        }

        return [
          'title' => $title,
          'link'  => $link,
          'tags'  => Bookmarks::tags($tags)
        ];
      };

      try {

        $user = Bookmarks::modify($user, function (array $bookmarks) use ($entry, $isFree, $freeLimit, $placeholder, &$error, &$event) {

          // UpdateBookmark: expects u_id (index), optional u_hash (fingerprint), u_title and u_link
          if (get('u_id') !== null) {

            $index = Bookmarks::find($bookmarks, Bookmarks::input('u_id'), Bookmarks::input('u_hash'));

            if ($index === null) {
              $error = 'This bookmark has changed in the meantime. Please reload the page and try again.';
              return null;
            }

            // same rule as the edit button in snippets/bookmarks.php
            if ($isFree && (count($bookmarks) > $freeLimit || ($bookmarks[$index]['title'] ?? null) === $placeholder)) {
              $error = 'Please become premium to edit your bookmarks.';
              return null;
            }

            if (($data = $entry('u')) === null) {
              return null;
            }

            $bookmarks[$index] = $data;
            return $bookmarks;
          }

          // AddBookmark: expects c_title and c_link
          if (get('c_title') !== null || get('c_link') !== null) {

            if (($data = $entry('c')) === null) {
              return null;
            }

            if (count($bookmarks) >= (int)option('bookmarkLimit', 10000)) {
              $error = 'You have reached the maximum number of bookmarks.';
              return null;
            }

            // analytics: no titles, links or tags, only what helps to understand usage
            $event = ['Add Bookmark Completed', [
              'plan'       => $isFree ? 'Free' : 'Premium',
              'tags'       => count(Str::split($data['tags'], ',')),
              'brand_logo' => site()->brandLogo($data['title']) ? 'yes' : 'no'
            ]];

            // free tier: past the limit the "become premium" card is added instead
            if ($isFree && count($bookmarks) >= $freeLimit) {
              $event = ['Free Limit Reached', []];
              $data = [
                'title' => $placeholder,
                'link'  => option('noPremiumLink'),
                'tags'  => option('noPremiumTags')
              ];
            }

            $bookmarks[] = $data;
            return $bookmarks;
          }

          // DeleteBookmark: expects d_bookmark (index) and optional d_hash (fingerprint)
          if (get('d_bookmark') !== null) {

            $index = Bookmarks::find($bookmarks, Bookmarks::input('d_bookmark'), Bookmarks::input('d_hash'));

            if ($index === null) {
              $error = 'This bookmark has changed in the meantime. Please reload the page and try again.';
              return null;
            }

            array_splice($bookmarks, $index, 1);
            return $bookmarks;
          }

          return null;
        });

      } catch (\Exception $e) {
        $error = option('debug') ? 'Your bookmarks could not be saved: ' . $e->getMessage() : 'Your bookmarks could not be saved!';
      }

      // SUCCESSFUL: POST/REDIRECT/GET (no duplicate bookmark on reload)
      if ($error === null) {
        if ($event !== null) {
          Analytics::track(...$event);
        }
        go($page->url());
      }
    }
  }

  // choose bookmarks from user (if exists) otherwise from page
  $bookmarks = $user ? $user->bookmarks()->yaml() : $page->bookmarks()->yaml();
  $bookmarks = is_array($bookmarks) ? array_values($bookmarks) : [];

  return [
    'error' => $error,
    'bookmarks' => $bookmarks
  ];
};
