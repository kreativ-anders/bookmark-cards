<?php

use Kirby\Cms\App as Kirby;

/**
 * Server-confirmed Pirsch events.
 *
 * Click events on submit buttons also fire for failed submissions and may get
 * lost when the page navigates away. Controllers queue an event once the action
 * really succeeded; snippets/analytics.php sends it on the next page view.
 * Never put personal data (email, titles, links, tags) into the meta values.
 */
class Analytics
{
  public const SESSION_KEY = 'analytics.events';

  public static function track(string $name, array $meta = []): void
  {
    $session = Kirby::instance()->session();
    $events  = $session->get(static::SESSION_KEY, []);

    $events[] = ['name' => $name, 'meta' => array_map('strval', $meta)];

    $session->set(static::SESSION_KEY, $events);
  }

  /**
   * Returns the queued events once and clears the queue
   */
  public static function pull(): array
  {
    $events = Kirby::instance()->session()->pull(static::SESSION_KEY, []);

    return is_array($events) ? $events : [];
  }
}

Kirby::plugin('bookmark-cards/analytics', []);
