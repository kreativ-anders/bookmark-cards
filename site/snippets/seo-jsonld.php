<?php
/**
 * Structured data for the landing page (schema.org: Organization, WebSite, WebApplication)
 * @var float|null $monthly monthly Premium price in EUR
 * @var float|null $price   yearly Premium price in EUR
 */
$url    = $site->url() . '/';
$offer  = fn (string $name, float $price, string $description, string|null $unit = null) => array_filter([
  '@type'              => 'Offer',
  'name'               => $name,
  'price'              => number_format($price, 2, '.', ''),
  'priceCurrency'      => 'EUR',
  'description'        => $description,
  'priceSpecification' => $unit ? [
    '@type'             => 'UnitPriceSpecification',
    'price'             => number_format($price, 2, '.', ''),
    'priceCurrency'     => 'EUR',
    'unitCode'          => $unit,
    'referenceQuantity' => ['@type' => 'QuantitativeValue', 'value' => 1, 'unitCode' => $unit],
  ] : null,
]);

$offers = [$offer('Free', 0, 'Up to ' . option('noPremiumLimit') . ' bookmarks with all core features')];

if (($monthly ?? null) !== null) {
  $offers[] = $offer('Premium (monthly)', (float)$monthly, 'Unlimited bookmarks, billed monthly', 'MON');
}

if (($price ?? null) !== null) {
  $offers[] = $offer('Premium (yearly)', (float)$price, 'Unlimited bookmarks, billed yearly', 'ANN');
}

$data = [
  '@context' => 'https://schema.org',
  '@graph'   => [
    [
      '@type' => 'Organization',
      '@id'   => 'https://kreativ-anders.de/#organization',
      'name'  => 'kreativ-anders',
      'url'   => 'https://kreativ-anders.de/',
      'logo'  => $site->url() . '/assets/images/kreativ-anders.svg',
    ],
    [
      '@type'      => 'WebSite',
      '@id'        => $url . '#website',
      'url'        => $url,
      'name'       => 'Bookmark.cards',
      'inLanguage' => 'en',
      'publisher'  => ['@id' => 'https://kreativ-anders.de/#organization'],
    ],
    [
      '@type'               => 'WebApplication',
      '@id'                 => $url . '#app',
      'name'                => 'Bookmark.cards',
      'url'                 => $url,
      'description'         => 'Free, privacy-friendly bookmark manager. Save links as visual cards, organize them with tags, search instantly and export to JSON or CSV.',
      'applicationCategory' => 'ProductivityApplication',
      'operatingSystem'     => 'Any (web browser)',
      'browserRequirements' => 'Requires JavaScript and a modern web browser',
      'inLanguage'          => 'en',
      'image'               => $site->url() . '/assets/images/og-image.png',
      'screenshot'          => $site->url() . '/assets/images/feature-brands.png',
      'featureList'         => ['Tags', 'Instant search', 'Brand logos on cards', 'JSON and CSV export', 'Offline access', 'Light and dark mode'],
      'isAccessibleForFree' => true,
      'offers'              => $offers,
      'publisher'           => ['@id' => 'https://kreativ-anders.de/#organization'],
    ],
  ],
];
?>
<script type="application/ld+json">
<?= json_encode($data, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT | JSON_HEX_TAG) ?>

</script>
