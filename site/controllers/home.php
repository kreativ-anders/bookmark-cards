<?php

return function ($kirby, $page) {

  $error = null;
  $event = null;
  $user  = $kirby->user();

  if ($user) {
    AccountActivity::touch($user);
  }

  if ($user && $kirby->request()->is('POST')) {

    if (csrf(get('csrf')) !== true) {

      $error = 'Invalid CSRF token! Please reload the page and try again.';

    } else {

      $isFree      = $user->isFreeTier();
      $freeLimit   = (int)option('noPremiumLimit');
      $placeholder = option('noPremiumTitle');

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

        $user = Bookmarks::modify($user, function (array $bookmarks) use ($user, $entry, $isFree, $freeLimit, $placeholder, &$error, &$event) {

          if (get('u_id') !== null) {

            $index = Bookmarks::find($bookmarks, Bookmarks::input('u_id'), Bookmarks::input('u_hash'));

            if ($index === null) {
              $error = 'This bookmark has changed in the meantime. Please reload the page and try again.';
              return null;
            }

            if (!Bookmarks::editable($user, $bookmarks, $bookmarks[$index])) {
              $error = 'Please become premium to edit your bookmarks.';
              return null;
            }

            if (($data = $entry('u')) === null) {
              return null;
            }

            $bookmarks[$index] = $data;
            return $bookmarks;
          }

          if (get('c_title') !== null || get('c_link') !== null) {

            if (($data = $entry('c')) === null) {
              return null;
            }

            if (count($bookmarks) >= (int)option('bookmarkLimit', 10000)) {
              $error = 'You have reached the maximum number of bookmarks.';
              return null;
            }

            // no titles, links or tags in analytics
            $event = ['Add Bookmark Completed', [
              'plan'       => $isFree ? 'Free' : 'Premium',
              'tags'       => count(Str::split($data['tags'], ',')),
              'brand_logo' => site()->brandLogo($data['title'], $data['link']) ? 'yes' : 'no'
            ]];

            if ($isFree && count($bookmarks) >= $freeLimit) {
              $event = ['Free Limit Reached', []];
              $data  = [
                'title' => $placeholder,
                'link'  => option('noPremiumLink'),
                'tags'  => option('noPremiumTags')
              ];
            }

            $bookmarks[] = $data;
            return $bookmarks;
          }

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

      // POST/REDIRECT/GET: a reload never adds the bookmark twice
      if ($error === null) {
        if ($event !== null) {
          Analytics::track(...$event);
        }
        go($page->url());
      }
    }
  }

  $bookmarks = $user ? $user->bookmarks()->yaml() : $page->bookmarks()->yaml();

  return [
    'error'     => $error,
    'bookmarks' => is_array($bookmarks) ? array_values($bookmarks) : []
  ];
};
