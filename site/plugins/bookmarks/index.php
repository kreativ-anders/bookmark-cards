<?php

use Kirby\Cms\App as Kirby;
use Kirby\Cms\User;
use Kirby\Content\VersionCache;
use Kirby\Data\Yaml;
use Kirby\Filesystem\Dir;
use Kirby\Toolkit\Str;

class Bookmarks
{
  // generous, only against abuse: existing bookmarks must stay editable
  public const MAX_TITLE = 1000;
  public const MAX_LINK  = 8192;
  public const MAX_TAGS  = 2000;

  // arrays (e.g. `c_title[]=x`) count as missing
  public static function input(string $key): string|null
  {
    $value = get($key);

    return is_string($value) ? trim($value) : null;
  }

  public static function link(string $link): string|null
  {
    if ($link === '' || static::isSafe($link) === false) {
      return null;
    }

    return $link;
  }

  // browsers ignore whitespace and control characters inside the scheme ("java\tscript:")
  public static function isSafe(string $link): bool
  {
    $scheme = preg_replace('/[\x00-\x20]+/', '', $link);

    return preg_match('/^(javascript|vbscript|data):/i', $scheme) !== 1;
  }

  // neutralizes unsafe links that were stored before validation existed
  public static function href(string $link): string
  {
    // the "become premium" card links to the trusted config value (live: a javascript: checkout click)
    if ($link !== '' && $link === option('noPremiumLink')) {
      return $link;
    }

    return static::isSafe($link) ? $link : '#';
  }

  // deduped case-insensitively, the first spelling wins
  public static function tags(string $tags): string
  {
    $unique = [];

    foreach (Str::split($tags, ',') as $tag) {
      $unique[mb_strtolower($tag)] ??= $tag;
    }

    return implode(', ', $unique);
  }

  // identifies a bookmark independent of its index, so a stale index (second tab) never hits the wrong one
  public static function fingerprint(array $bookmark): string
  {
    return substr(hash('sha256', ($bookmark['title'] ?? '') . "\n" . ($bookmark['link'] ?? '') . "\n" . ($bookmark['tags'] ?? '')), 0, 16);
  }

  // without fingerprint (pages rendered before it existed) the index is trusted
  public static function find(array $bookmarks, string|null $index, string|null $fingerprint): int|null
  {
    // main.js changeData() sends '' for index 0
    $index = $index === '' ? '0' : $index;

    if ($index === null || ctype_digit($index) === false) {
      return null;
    }

    $index = (int)$index;

    if ($fingerprint === null || $fingerprint === '') {
      return isset($bookmarks[$index]) ? $index : null;
    }

    if (isset($bookmarks[$index]) && static::fingerprint($bookmarks[$index]) === $fingerprint) {
      return $index;
    }

    foreach ($bookmarks as $i => $bookmark) {
      if (static::fingerprint($bookmark) === $fingerprint) {
        return $i;
      }
    }

    return null;
  }

  /**
   * Free users may edit up to the limit (never the upsell card), premium users always
   */
  public static function editable(User $user, array $bookmarks, array $bookmark): bool
  {
    if ($user->isPremium()) {
      return true;
    }

    return $user->isFreeTier()
      && count($bookmarks) <= (int)option('noPremiumLimit')
      && ($bookmark['title'] ?? null) !== option('noPremiumTitle');
  }

  /**
   * Read-modify-write under an exclusive lock, so concurrent requests don't overwrite each other.
   * $callback receives the fresh bookmarks and returns the new list (or null for no change).
   */
  public static function modify(User $user, callable $callback): User
  {
    // the lock is best effort: if the cache folder is not writable, save without it (as before)
    $lock = null;

    try {
      $dir = Kirby::instance()->root('cache') . '/locks';
      Dir::make($dir);
      $lock = fopen($dir . '/bookmarks-' . $user->id() . '.lock', 'c') ?: null;
      $lock && flock($lock, LOCK_EX);
    } catch (Throwable $e) {
      error_log('bookmarks: could not lock ' . $user->id() . ': ' . $e->getMessage());
    }

    try {
      // another request may have written since this one read the content (VersionCache is @unstable in Kirby 5)
      if (method_exists(VersionCache::class, 'reset')) {
        VersionCache::reset();
      }

      $bookmarks = $user->bookmarks()->yaml();
      $bookmarks = array_values(is_array($bookmarks) ? $bookmarks : []);
      $result    = $callback($bookmarks);

      if ($result === null) {
        return $user;
      }

      return $user->update(['Bookmarks' => Yaml::encode(array_values($result))]);
    } finally {
      if ($lock) {
        flock($lock, LOCK_UN);
        fclose($lock);
      }
    }
  }

  /**
   * Imports a user.json export (or a plain list of bookmarks), validated like the add form.
   * Not exposed in the frontend yet: run site/plugins/bookmarks/import.php from the CLI.
   * Appends by default (exact duplicates are skipped), $replace drops the existing bookmarks first.
   * Returns ['imported' => int, 'skipped' => list of [entry, reason]].
   */
  public static function import(User $user, array $export, bool $replace = false): array
  {
    $entries  = $export['Bookmarks'] ?? $export;
    $imported = [];
    $skipped  = [];

    foreach (is_array($entries) ? $entries : [] as $entry) {
      $title = is_array($entry) && is_string($entry['title'] ?? null) ? trim($entry['title']) : '';
      $link  = is_array($entry) && is_string($entry['link'] ?? null) ? trim($entry['link']) : '';
      $tags  = is_array($entry) && is_string($entry['tags'] ?? null) ? trim($entry['tags']) : '';

      if (mb_strlen($title) > static::MAX_TITLE || mb_strlen($link) > static::MAX_LINK || mb_strlen($tags) > static::MAX_TAGS) {
        $skipped[] = [$entry, 'too long'];
        continue;
      }

      if (($link = static::link($link)) === null) {
        $skipped[] = [$entry, 'missing or unsafe link'];
        continue;
      }

      $imported[] = ['title' => $title, 'link' => $link, 'tags' => static::tags($tags)];
    }

    $count = 0;

    static::modify($user, function (array $bookmarks) use ($imported, $replace, &$count, &$skipped) {
      $bookmarks = $replace ? [] : $bookmarks;
      $known     = array_flip(array_map(static::fingerprint(...), $bookmarks));

      foreach ($imported as $bookmark) {
        if (isset($known[$fingerprint = static::fingerprint($bookmark)])) {
          $skipped[] = [$bookmark, 'duplicate'];
          continue;
        }

        $known[$fingerprint] = true;
        $bookmarks[]         = $bookmark;
        $count++;
      }

      return $count > 0 || $replace ? $bookmarks : null;
    });

    return ['imported' => $count, 'skipped' => $skipped];
  }
}

Kirby::plugin('bookmark-cards/bookmarks', []);
