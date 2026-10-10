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
}

Kirby::plugin('bookmark-cards/bookmarks', []);
