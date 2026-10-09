<?php
/**
 * @var float|null $price   yearly Premium price in EUR (resolved once in home.php), null if Stripe is unreachable
 * @var float|null $monthly monthly Premium price in EUR (what the checkout buttons book), null if Stripe is unreachable
 */
$price   = $price ?? null;
$monthly = $monthly ?? null;
?>
<section class="container" id="pricing" style="text-align: center;">
  <h2>Simple pricing</h2>
  <p><em>Start free, upgrade when your bookmark collection grows.</em></p>
  <div class="grid">
    <article>
      <header>
        <h3>Basic</h3>
      </header>
      <p class="price">0€ <small>/ month</small></p>
      <p>Try Bookmark.cards for free — store up to <?= option('noPremiumLimit'); ?> bookmarks and use all core features.</p>
      <a id="pricing-register" href="<?= url('register') ?>" role="button" class="primary" data-pirsch-event="Open Register Modal" data-pirsch-meta-source="Pricing">Get started (Free)</a>
    </article>
    <article>
      <header>
        <h3>Premium</h3>
      </header>
      <p class="price"><?= $monthly !== null ? esc((string)$monthly) . '€' : '—' ?> <small>/ month</small></p>
      <?php if ($price !== null): ?>
      <p><small>or <?= esc((string)$price) ?>€ / year</small></p>
      <?php endif ?>
      <p>Unlimited bookmarks and no banners. Pay monthly or yearly, cancel anytime in your settings.</p>
    </article>
  </div>
</section>
