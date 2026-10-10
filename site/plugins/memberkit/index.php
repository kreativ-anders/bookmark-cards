<?php

class Memberkit
{
  public const PRICES_TTL       = 720;
  public const PRICES_RETRY_TTL = 10;

  public static function stripe(): \Stripe\StripeClient
  {
    return new \Stripe\StripeClient(array_filter([
      'api_key'  => option('kreativ-anders.memberkit.secretKey'),
      'api_base' => option('kreativ-anders.memberkit.apiBase'),
    ]));
  }

  /**
   * Tier index => price in major units (null if Stripe could not resolve it).
   * Cached, so pages showing prices never wait for Stripe on every request;
   * failures are retried after a few minutes only.
   */
  public static function prices(): array
  {
    $cache = kirby()->cache('kreativ-anders.memberkit');

    if (is_array($prices = $cache->get('prices'))) {
      return $prices;
    }

    $prices   = [];
    $complete = true;

    foreach (option('kreativ-anders.memberkit.tiers', []) as $index => $tier) {
      if (empty($tier['price'])) {
        continue;
      }

      try {
        $prices[$index] = static::stripe()->prices->retrieve($tier['price'])->unit_amount / 100;
      } catch (Throwable $e) {
        error_log('memberkit: could not retrieve stripe price ' . $tier['price'] . ': ' . $e->getMessage());
        $prices[$index] = null;
        $complete = false;
      }
    }

    $cache->set('prices', $prices, $complete ? static::PRICES_TTL : static::PRICES_RETRY_TTL);

    return $prices;
  }

  public static function flushPrices(): void
  {
    kirby()->cache('kreativ-anders.memberkit')->remove('prices');
  }
}

Kirby::plugin('kreativ-anders/memberkit', [
  'options'      => include_once __DIR__ . '/options.php',
  'snippets'     => include_once __DIR__ . '/snippets.php',
  'hooks'        => include_once __DIR__ . '/hooks.php',
  'routes'       => include_once __DIR__ . '/routes.php',
  'userMethods'  => include_once __DIR__ . '/userMethods.php',
  'usersMethods' => include_once __DIR__ . '/usersMethods.php',
  'siteMethods'  => include_once __DIR__ . '/siteMethods.php',
]);
