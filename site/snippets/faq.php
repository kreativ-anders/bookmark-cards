<?php
// rendered as HTML and as FAQPage structured data from the same list
$faqs = [
  ['What is Bookmark.cards?',
   'Bookmark.cards is a free, web-based bookmark manager. You save links as cards, organize them with tags and find them again with an instant search, on any device with a browser.'],
  ['Is Bookmark.cards free?',
   'Yes. The free plan stores up to ' . option('noPremiumLimit') . ' bookmarks with all core features. Premium removes the limit and supports the development of the app.'],
  ['How is my data protected?',
   'Bookmark.cards stores only your email address, your password hash and your bookmarks. There are no ads, no tracking cookies and no selling of data. Payments are handled by Stripe.'],
  ['Can I export my bookmarks?',
   'Yes. In your settings you can download all bookmarks as JSON or CSV at any time, for backups or to move them to another bookmark manager.'],
  ['How do the brand logos on the cards work?',
   'When the link or title of a bookmark matches a known brand, for example GitHub or Notion, the card shows its logo. The logo collection is open source and anyone can contribute.'],
  ['Does Bookmark.cards work offline?',
   'Yes. Logged-in users can install Bookmark.cards as a progressive web app. Your bookmarks stay available when you are offline.'],
];
?>
<section id="faq" class="container">
  <h2>Frequently asked questions</h2>
  <p>Everything you need to know about the bookmark manager before you start.</p>
  <?php foreach ($faqs as [$question, $answer]): ?>
  <details>
    <summary><h3><?= esc($question) ?></h3></summary>
    <p><?= esc($answer) ?></p>
  </details>
  <?php endforeach ?>
</section>

<script type="application/ld+json">
<?= json_encode([
  '@context'   => 'https://schema.org',
  '@type'      => 'FAQPage',
  'mainEntity' => array_map(fn ($faq) => [
    '@type'          => 'Question',
    'name'           => $faq[0],
    'acceptedAnswer' => ['@type' => 'Answer', 'text' => $faq[1]],
  ], $faqs),
], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT) ?>

</script>
